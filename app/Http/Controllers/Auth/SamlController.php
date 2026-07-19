<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Connector\Services\AuthBridgeService;
use App\Domain\Sso\Services\SamlServiceProvider;
use App\Http\Controllers\Controller;
use App\Models\Tenant\SamlIdentityProvider;
use App\Models\Tenant\SamlSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SamlController extends Controller
{
    public function __construct(
        protected SamlServiceProvider $saml,
    ) {}

    /**
     * SP Metadata — return XML describing this Service Provider.
     */
    public function metadata(string $idpId)
    {
        $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))
            ->where('id', $idpId)
            ->where('is_active', true)
            ->firstOrFail();

        $metadata = $this->saml->getMetadata($idp);

        return response($metadata, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Initiate SAML login — redirect to IdP.
     */
    public function login(string $idpId, Request $request)
    {
        $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))
            ->where('id', $idpId)
            ->where('is_active', true)
            ->firstOrFail();

        $relayState = $request->query('relay', '/admin');
        $requestId = Str::uuid()->toString();

        // Store session
        SamlSession::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'request_id' => $requestId,
            'relay_state' => $relayState,
            'created_at' => now(),
            'expires_at' => now()->addMinutes((int) config('sso.session.ttl_minutes', 5)),
        ]);

        $redirectUrl = $this->saml->initiateLogin($idp, $requestId, $relayState);

        return redirect()->away($redirectUrl);
    }

    /**
     * Assertion Consumer Service — process SAML response from IdP.
     */
    public function acs(Request $request)
    {
        $samlResponse = $request->input('SAMLResponse');
        $relayState = $request->input('RelayState', '/admin');

        if (! $samlResponse) {
            return response()->json(['error' => 'Missing SAMLResponse'], 400);
        }

        // Decode and parse the SAML response
        $xml = base64_decode($samlResponse);
        $userId = null;

        try {
            // Find the matching IdP based on issuer in the response
            $issuer = $this->extractIssuer($xml);
            $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))
                ->where('entity_id', $issuer)
                ->where('is_active', true)
                ->first();

            if (! $idp) {
                throw new \RuntimeException('Unknown IdP issuer: ' . $issuer);
            }

            // Process the SAML response — extract user attributes
            $attributes = $this->saml->processResponse($idp, $xml);

            // Find or create user
            $email = $attributes['email'] ?? null;
            if (! $email) {
                throw new \RuntimeException('Email not provided in SAML response');
            }

            $user = $this->findOrCreateUser($email, $attributes, $idp);
            auth()->login($user);

            // Map roles
            $this->saml->mapRoles($user, $idp, $attributes);

            $redirect = $relayState ?: '/admin';
            return redirect()->to($redirect);
        } catch (\Throwable $e) {
            return response()->view('errors.saml-failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Single Logout Service.
     */
    public function sls(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function extractIssuer(string $xml): ?string
    {
        if (preg_match('/<saml:Issuer[^>]*>([^<]+)<\/saml:Issuer>/i', $xml, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function findOrCreateUser(string $email, array $attributes, SamlIdentityProvider $idp)
    {
        $user = \App\Models\Central\User::where('email', $email)->first();

        if (! $user) {
            if (! config('sso.auto_create_users', true)) {
                throw new \RuntimeException('User does not exist and auto-create is disabled.');
            }

            $user = \App\Models\Central\User::create([
                'id' => Str::uuid(),
                'name' => $attributes['name'] ?? $email,
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            \DB::table('tenant_users')->insert([
                'id' => Str::uuid(),
                'tenant_id' => tenant('id'),
                'user_id' => $user->id,
                'role' => config('sso.default_role_on_create', 'editor'),
                'joined_at' => now(),
            ]);
        }

        if (config('sso.update_user_attributes_on_login', true)) {
            $user->update([
                'name' => $attributes['name'] ?? $user->name,
            ]);
        }

        return $user;
    }
}
