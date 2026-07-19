<?php

namespace App\Domain\Billing\Gateways;

interface GatewayInterface
{
    /**
     * Charge a payment method.
     *
     * @return array{success: bool, transaction_id: ?string, error: ?string, raw: array}
     */
    public function charge(float $amount, string $currency, array $paymentData): array;

    /**
     * Refund a charge.
     */
    public function refund(string $transactionId, ?float $amount = null): array;

    /**
     * Create a subscription.
     */
    public function createSubscription(string $customerId, string $planId): array;

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(string $subscriptionId): array;

    /**
     * Create a customer.
     */
    public function createCustomer(array $data): array;
}
