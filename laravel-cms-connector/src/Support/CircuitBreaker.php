<?php

namespace Platform\CmsConnector\Support;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    protected string $stateKey = 'cms-connector:circuit:state';
    protected string $failKey = 'cms-connector:circuit:failures';
    protected string $openKey = 'cms-connector:circuit:opened_at';

    public function isOpen(): bool
    {
        if (Cache::get($this->stateKey) !== 'open') return false;
        $openedAt = Cache::get($this->openKey);
        $resetSeconds = (int) config('cms-connector.circuit_breaker.reset_seconds', 60);
        if ($openedAt && (time() - $openedAt) > $resetSeconds) {
            Cache::put($this->stateKey, 'half-open', 300);
            return false;
        }
        return true;
    }

    public function recordSuccess(): void
    {
        Cache::forget($this->failKey);
        Cache::put($this->stateKey, 'closed', 0);
        Cache::forget($this->openKey);
    }

    public function recordFailure(): void
    {
        $failures = Cache::increment($this->failKey);
        $threshold = (int) config('cms-connector.circuit_breaker.failure_threshold', 5);
        if ($failures >= $threshold) {
            Cache::put($this->stateKey, 'open', 0);
            Cache::put($this->openKey, time(), 0);
        }
    }
}
