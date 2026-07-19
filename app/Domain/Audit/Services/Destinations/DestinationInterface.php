<?php

namespace App\Domain\Audit\Services\Destinations;

interface DestinationInterface
{
    /**
     * Send an audit event payload to the destination.
     *
     * @return array{status: int, body: string}
     */
    public function send(array $config, array $payload): array;
}
