<?php

namespace App\Domain\Connector\Events;

use App\Models\Central\RegisteredConnector;
use Illuminate\Foundation\Events\Dispatchable;

class IncomingWebhookReceived
{
    use Dispatchable;

    public function __construct(
        public RegisteredConnector $connector,
        public string $eventType,
        public array $data,
        public string $eventId,
    ) {}
}
