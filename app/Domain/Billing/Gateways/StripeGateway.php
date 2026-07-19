<?php

namespace App\Domain\Billing\Gateways;

use Illuminate\Support\Facades\Http;

class StripeGateway implements GatewayInterface
{
    protected string $apiKey;
    protected string $apiBase = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->apiKey = config('billing.gateways.stripe.secret', env('STRIPE_SECRET'));
    }

    public function charge(float $amount, string $currency, array $paymentData): array
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->apiKey, '')
                ->post("{$this->apiBase}/charges", [
                    'amount' => (int) ($amount * 100), // Stripe expects cents
                    'currency' => strtolower($currency),
                    'source' => $paymentData['token'] ?? null,
                    'customer' => $paymentData['customer_id'] ?? null,
                    'description' => $paymentData['description'] ?? null,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => $data['paid'] ?? false,
                    'transaction_id' => $data['id'] ?? null,
                    'error' => null,
                    'raw' => $data,
                ];
            }

            return [
                'success' => false,
                'transaction_id' => null,
                'error' => $response->json('error.message', 'Unknown error'),
                'raw' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'transaction_id' => null, 'error' => $e->getMessage(), 'raw' => []];
        }
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        $params = ['charge' => $transactionId];
        if ($amount !== null) {
            $params['amount'] = (int) ($amount * 100);
        }

        $response = Http::asForm()
            ->withBasicAuth($this->apiKey, '')
            ->post("{$this->apiBase}/refunds", $params);

        return [
            'success' => $response->successful(),
            'refund_id' => $response->json('id'),
            'raw' => $response->json(),
        ];
    }

    public function createSubscription(string $customerId, string $planId): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->apiKey, '')
            ->post("{$this->apiBase}/subscriptions", [
                'customer' => $customerId,
                'items' => [['price' => $planId]],
            ]);

        return [
            'success' => $response->successful(),
            'subscription_id' => $response->json('id'),
            'raw' => $response->json(),
        ];
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->apiKey, '')
            ->delete("{$this->apiBase}/subscriptions/{$subscriptionId}");

        return [
            'success' => $response->successful(),
            'raw' => $response->json(),
        ];
    }

    public function createCustomer(array $data): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->apiKey, '')
            ->post("{$this->apiBase}/customers", [
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

        return [
            'success' => $response->successful(),
            'customer_id' => $response->json('id'),
            'raw' => $response->json(),
        ];
    }
}
