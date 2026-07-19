<?php

namespace App\Observers;

use App\Models\Central\Domain;
use Illuminate\Support\Facades\Cache;

class DomainObserver
{
    public function updated(Domain $domain): void
    {
        Cache::forget("domain:{$domain->domain}");
        Cache::forget("tenant:domains:{$domain->tenant_id}");
    }

    public function deleted(Domain $domain): void
    {
        Cache::forget("domain:{$domain->domain}");
        Cache::forget("tenant:domains:{$domain->tenant_id}");
    }
}
