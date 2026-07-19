<?php

namespace Platform\CmsConnector\Support;

use Illuminate\Support\Facades\Cache;

class CacheFallback
{
    public function get(string $key): ?array { $val = Cache::get($key); return is_array($val) ? $val : null; }
    public function put(string $key, array $value, int $ttl): void { Cache::put($key, $value, $ttl); }
    public function forget(string $key): void { Cache::forget($key); }
}
