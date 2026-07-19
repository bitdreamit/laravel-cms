<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Gateways\GatewayInterface;
use App\Domain\Billing\Gateways\StripeGateway;
use App\Domain\Billing\Gateways\SslcommerzGateway;
use App\Domain\Billing\Gateways\BkashGateway;
use InvalidArgumentException;

class GatewayManager
{
    protected array $gateways = [];

    public function gateway(string $name): GatewayInterface
    {
        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        return $this->gateways[$name] = match ($name) {
            'stripe' => app(StripeGateway::class),
            'sslcommerz' => app(SslcommerzGateway::class),
            'bkash' => app(BkashGateway::class),
            default => throw new InvalidArgumentException("Unknown gateway: {$name}"),
        };
    }

    public function defaultGateway(): GatewayInterface
    {
        return $this->gateway(config('billing.default_gateway', 'stripe'));
    }
}
