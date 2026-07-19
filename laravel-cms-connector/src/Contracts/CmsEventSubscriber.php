<?php

namespace Platform\CmsConnector\Contracts;

interface CmsEventSubscriber
{
    public function handle(string $eventType, array $payload): void;
}
