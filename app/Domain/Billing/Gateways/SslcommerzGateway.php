<?php

namespace App\Domain\Billing\Gateways;

use Illuminate\Support\Facades\Http;

class SslcommerzGateway implements GatewayInterface
{
    protected string $storeId;
    protected string $storePassword;
    protected string $apiBase;

    public function __construct()
    {
        $this->storeId = config('billing.gateways.sslcommerz.store_id', env('SSLCOMMERZ_STORE_ID'));
        $this->storePassword = config('billing.gateways.sslcommerz.store_password', env('SSLCOMMERZ_STORE_PASSWORD'));
        $this->apiBase = config('billing.gateways.sslcommerz.sandbox', true)
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
    }

    public function charge(float $amount, string $currency, array $paymentData): array
    {
        $response = Http::asForm()->post($this->apiBase, [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $amount,
            'currency' => $currency,
            'tran_id' => $paymentData['transaction_id'] ?? uniqid('ssl_'),
            'success_url' => $paymentData['success_url'] ?? null,
            'fail_url' => $paymentData['fail_url'] ?? null,
            'cancel_url' => $paymentData['cancel_url'] ?? null,
            'cus_name' => $paymentData['customer_name'] ?? 'Customer',
            'cus_email' => $paymentData['customer_email'] ?? 'customer@example.com',
            'cus_phone' => $paymentData['customer_phone'] ?? '0000000000',
            'product_name' => $paymentData['description'] ?? 'CMS Subscription',
            'product_category' => 'subscription',
            'product_profile' => 'non-physical-goods',
        ]);

        $data = $response->json();

        return [
            'success' => ($data['status'] ?? '') === 'SUCCESS',
            'transaction_id' => $data['tran_id'] ?? null,
            'payment_url' => $data['GatewayPageURL'] ?? null,
            'error' => $data['failedreason'] ?? null,
            'raw' => $data,
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        $response = Http::asForm()->post('https://sandbox.sslcommerz.com/validator/api/merchantTrans/transactionAPI.php', [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'bank_tran_id' => $transactionId,
            'refund_amount' => $amount,
            'refund_remarks' => 'Customer requested refund',
        ]);

        return [
            'success' => $response->successful(),
            'raw' => $response->json(),
        ];
    }

    public function createSubscription(string $customerId, string $planId): array
    {
        // SSLCommerz doesn't natively support subscriptions — handled via recurring billing cron
        return ['success' => false, 'error' => 'Subscriptions not natively supported.'];
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        return ['success' => true];
    }

    public function createCustomer(array $data): array
    {
        // SSLCommerz doesn't have a customer object — return the input data
        return ['success' => true, 'customer_id' => $data['email'] ?? null];
    }
}
