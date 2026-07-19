<?php

namespace App\Domain\Connector\Services;

use App\Models\Central\RegisteredConnector;
use App\Models\Central\Tenant;
use App\Models\Central\TenantUser;
use App\Models\Central\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class AuthBridgeService
{
    /**
     * Generate a JWT for SSO bridge — host app signs this and redirects to CMS.
     */
    public function generateSsoToken(array $user, string $secret, int $ttlSeconds = 60): string
    {
        $payload = array_merge($user, [
            'iat' => time(),
            'exp' => time() + $ttlSeconds,
            'iss' => 'host-app',
            'aud' => 'cms-platform',
        ]);

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Verify a JWT from the host app.
     * Verifies signature AND enforces iss/aud claim checks.
     */
    public function verifySsoToken(string $token, string $secret): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }

        // Enforce issuer and audience claims — signature alone is not enough.
        // A token minted for a different audience must NOT be accepted here.
        if (($decoded->iss ?? null) !== 'host-app') {
            return null;
        }
        if (($decoded->aud ?? null) !== 'cms-platform') {
            return null;
        }

        return $decoded;
    }

    /**
     * Find or create a CMS user from SSO payload.
     *
     * SECURITY: This does NOT silently attach an existing user to a new tenant.
     * If a user with the email already exists but is NOT already a member of the
     * current tenant, we require an explicit "auto_create_users" flag AND an
     * invitation/opt-in record. Without invitation, the SSO attempt is rejected
     * so that an attacker controlling a host app cannot grant themselves access
     * to arbitrary tenants just by minting a JWT with a victim's email.
     */
    public function findOrCreateUser(object $payload, string $defaultRole = 'editor'): User
    {
        $tenantId = tenant('id');
        if (! $tenantId) {
            throw new \RuntimeException('SSO bridge requires tenant context.');
        }

        $email = $payload->email ?? null;
        if (! $email) {
            throw new \RuntimeException('SSO payload missing email.');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // User exists — check if they're already a member of THIS tenant.
            $alreadyMember = TenantUser::where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyMember) {
                // Legitimate login — update attributes if SSO says so.
                if (config('sso.update_user_attributes_on_login', true) && isset($payload->name)) {
                    $user->update(['name' => $payload->name]);
                }
                return $user;
            }

            // User exists but is NOT a member of this tenant.
            // Do NOT silently grant access. Require either:
            //   (a) auto_create_users=true AND an existing invitation, OR
            //   (b) auto_create_users=false (then reject).
            if (! config('sso.auto_create_users', true)) {
                throw new \RuntimeException('User exists but is not a member of this tenant, and auto_create_users is disabled.');
            }

            $invitation = \DB::table('tenant_user_invitations')
                ->where('tenant_id', $tenantId)
                ->where('email', $email)
                ->where('accepted_at', null)
                ->where('expires_at', '>', now())
                ->first();

            if (! $invitation) {
                throw new \RuntimeException('User exists but is not a member of this tenant, and no pending invitation was found. An Owner/Admin of this tenant must invite them first.');
            }

            // Mark invitation accepted and attach user to tenant.
            \DB::table('tenant_user_invitations')
                ->where('id', $invitation->id)
                ->update(['accepted_at' => now(), 'user_id' => $user->id]);

            TenantUser::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role' => $invitation->role ?? $defaultRole,
                'invited_by' => $invitation->invited_by ?? null,
                'joined_at' => now(),
            ]);

            return $user;
        }

        // User does not exist at all — only create if auto_create_users is on.
        if (! config('sso.auto_create_users', true)) {
            throw new \RuntimeException('User does not exist and auto_create_users is disabled.');
        }

        $user = User::create([
            'id' => Str::uuid(),
            'name' => $payload->name ?? $payload->email,
            'email' => $payload->email,
            'password' => bcrypt(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        TenantUser::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'role' => $defaultRole,
            'joined_at' => now(),
        ]);

        return $user;
    }
}
