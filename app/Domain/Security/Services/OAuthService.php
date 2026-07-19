<?php

namespace App\Domain\Security\Services;

use App\Models\Central\User;
use App\Models\Central\OauthConnection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthService
{
    public function redirect(string $provider)
    {
        $allowedProviders = ['google', 'github', 'gitlab', 'facebook', 'twitter', 'linkedin', 'microsoft'];

        if (! in_array($provider, $allowedProviders)) {
            throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}");
        }

        return Socialite::driver($provider)->redirect();
    }

    public function handleCallback(string $provider): User
    {
        $oauthUser = Socialite::driver($provider)->user();

        $connection = OauthConnection::where('provider', $provider)
            ->where('provider_id', $oauthUser->getId())
            ->first();

        if ($connection) {
            $this->updateTokens($connection, $oauthUser);
            return $connection->user;
        }

        $user = User::where('email', $oauthUser->getEmail())->first();

        if (! $user) {
            $user = $this->createUserFromOAuth($oauthUser, $provider);
        }

        $this->createConnection($user, $provider, $oauthUser);

        return $user;
    }

    public function linkProvider(User $user, string $provider, $oauthUser): OauthConnection
    {
        return OauthConnection::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $oauthUser->getId(),
            'provider_email' => $oauthUser->getEmail(),
            'access_token' => encrypt($oauthUser->token),
            'refresh_token' => $oauthUser->refreshToken ? encrypt($oauthUser->refreshToken) : null,
            'expires_at' => $oauthUser->expiresIn ? now()->addSeconds($oauthUser->expiresIn) : null,
            'data' => [
                'nickname' => $oauthUser->getNickname(),
                'name' => $oauthUser->getName(),
                'avatar' => $oauthUser->getAvatar(),
            ],
        ]);
    }

    public function unlinkProvider(User $user, string $provider): bool
    {
        if ($user->password === null && $user->oauthConnections()->count() === 1) {
            throw new \RuntimeException('Cannot unlink the only authentication method. Set a password first.');
        }

        return $user->oauthConnections()->where('provider', $provider)->delete() > 0;
    }

    public function listLinkedProviders(User $user): array
    {
        return $user->oauthConnections()
            ->get()
            ->map(fn($c) => [
                'provider' => $c->provider,
                'provider_email' => $c->provider_email,
                'linked_at' => $c->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    protected function createUserFromOAuth($oauthUser, string $provider): User
    {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => $oauthUser->getName() ?: $oauthUser->getNickname(),
            'email' => $oauthUser->getEmail(),
            'password' => null,
            'avatar' => $oauthUser->getAvatar(),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        activity()
            ->performedOn($user)
            ->withProperties(['provider' => $provider, 'email' => $oauthUser->getEmail()])
            ->log('user_created_via_oauth');

        return $user;
    }

    protected function createConnection(User $user, string $provider, $oauthUser): OauthConnection
    {
        return $this->linkProvider($user, $provider, $oauthUser);
    }

    protected function updateTokens(OauthConnection $connection, $oauthUser): void
    {
        $connection->update([
            'access_token' => encrypt($oauthUser->token),
            'refresh_token' => $oauthUser->refreshToken ? encrypt($oauthUser->refreshToken) : null,
            'expires_at' => $oauthUser->expiresIn ? now()->addSeconds($oauthUser->expiresIn) : null,
        ]);
    }
}
