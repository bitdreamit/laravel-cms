<?php

namespace App\Domain\Connector\Services;

use App\Models\Central\RegisteredConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ConnectorManager
{
    /**
     * Register a new connector for a tenant.
     *
     * @return array{connector: RegisteredConnector, api_token: string, webhook_secret: string}
     */
    public function register(string $tenantId, string $name, string $baseUrl, array $subscribedEvents = [], array $syncableCollections = []): array
    {
        $webhookSecret = Str::random(64);

        // Create Sanctum token via the User model (using platform's service account)
        $serviceUser = \App\Models\Central\User::where('is_platform_super_admin', true)->first();
        $token = $serviceUser?->createToken('connector:' . $name, ['connector'])->plainTextToken ?? '';

        $connector = RegisteredConnector::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => $name,
            'connector_type' => 'laravel',
            'base_url' => $baseUrl,
            'webhook_secret' => $webhookSecret,
            'subscribed_events' => $subscribedEvents,
            'syncable_collections' => $syncableCollections,
            'is_active' => true,
        ]);

        return [
            'connector' => $connector,
            'api_token' => $token,
            'webhook_secret' => $webhookSecret,
        ];
    }

    /**
     * Revoke a connector.
     */
    public function revoke(RegisteredConnector $connector): void
    {
        // Revoke associated Sanctum token (if any)
        if ($connector->api_token_id) {
            \Laravel\Sanctum\PersonalAccessToken::find($connector->api_token_id)?->delete();
        }

        $connector->update(['is_active' => false]);
    }

    /**
     * Verify an incoming webhook HMAC signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature, RegisteredConnector $connector): bool
    {
        $expected = hash_hmac('sha256', $payload, $connector->webhook_secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Dispatch a webhook to a connector's webhook_url.
     */
    public function dispatchWebhook(RegisteredConnector $connector, string $eventType, array $data): void
    {
        if (! $connector->webhook_url) return;
        if (! $connector->isSubscribedTo($eventType)) return;

        $payload = [
            'event' => $eventType,
            'data' => $data,
            'tenant_id' => $connector->tenant_id,
            'source' => 'cms-platform',
            'timestamp' => now()->toIso8601String(),
            'event_id' => Str::uuid()->toString(),
        ];

        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $connector->webhook_secret);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Cms-Signature' => $signature,
            'X-Cms-Event' => $eventType,
        ])
            ->timeout(30)
            ->retry(3, 100)
            ->post($connector->webhook_url, $payload);
    }

    /**
     * Find a connector by its API token.
     */
    public function findByToken(string $token): ?RegisteredConnector
    {
        $tokenParts = explode('|', $token, 2);
        if (count($tokenParts) !== 2) return null;

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (! $accessToken) return null;

        return RegisteredConnector::where('api_token_id', $accessToken->id)
            ->where('is_active', true)
            ->first();
    }
}
