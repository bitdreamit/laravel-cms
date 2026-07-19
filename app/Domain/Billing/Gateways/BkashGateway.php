<?php

namespace App\Domain\Billing\Gateways;

use Illuminate\Support\Facades\Http;

class BkashGateway implements GatewayInterface
{
    protected string $appId;
    protected string $appSecret;
    protected string $username;
    protected string $password;
    protected string $apiBase;

    public function __construct()
    {
        $this->appId = config('billing.gateways.bkash.app_id', env('BKASH_APP_ID'));
        $this->appSecret = config('billing.gateways.bkash.app_secret', env('BKASH_APP_SECRET'));
        $this->username = config('billing.gateways.bkash.username', env('BKASH_USERNAME'));
        $this->password = config('billing.gateways.bkash.password', env('BKASH_PASSWORD'));
        $this->apiBase = config('billing.gateways.bkash.sandbox', true)
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function charge(float $amount, string $currency, array $paymentData): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key' => $this->appId,
        ])->post("{$this->apiBase}/tokenized/checkout/create", [
            'mode' => '0011',
            'amount' => (string) $amount,
            'currency' => 'BDT', // bKash only supports BDT
            'payerReference' => $paymentData['customer_id'] ?? 'customer',
            'callbackURL' => $paymentData['callback_url'] ?? null,
            'merchantInvoiceNumber' => $paymentData['invoice_number'] ?? uniqid('bkash_'),
        ]);

        $data = $response->json();
        return [
            'success' => ($data['statusCode'] ?? '') === '0000',
            'transaction_id' => $data['paymentID'] ?? null,
            'payment_url' => $data['bkashURL'] ?? null,
            'error' => $data['statusMessage'] ?? null,
            'raw' => $data,
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key' => $this->appId,
        ])->post("{$this->apiBase}/tokenized/checkout/payment/refund", [
            'paymentID' => $transactionId,
            'amount' => (string) $amount,
        ]);

        return [
            'success' => $response->successful(),
            'raw' => $response->json(),
        ];
    }

    public function createSubscription(string $customerId, string $planId): array
    {
        return ['success' => false, 'error' => 'bKash does not support subscriptions natively.'];
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        return ['success' => true];
    }

    public function createCustomer(array $data): array
    {
        return ['success' => true, 'customer_id' => $data['phone'] ?? null];
    }

    protected function getToken(): string
    {
        $cached = cache()->get('bkash_token');
        if ($cached) return $cached;

        $response = Http::withHeaders([
            'username' => $this->username,
            'password' => $this->password,
        ])->post("{$this->apiBase}/tokenized/checkout/token/grant", [
            'app_key' => $this->appId,
            'app_secret' => $this->appSecret,
        ]);

        $token = $response->json('id_token');
        cache()->put('bkash_token', $token, now()->addMinutes(50));

        return $token;
    }
}
