<?php

namespace App\Observers;

use App\Models\Tenant\Blueprint;
use Illuminate\Support\Facades\Cache;

class BlueprintObserver
{
    public function updated(Blueprint $blueprint): void
    {
        Cache::forget("blueprint:{$blueprint->tenant_id}:{$blueprint->id}");
    }

    public function deleted(Blueprint $blueprint): void
    {
        Cache::forget("blueprint:{$blueprint->tenant_id}:{$blueprint->id}");
    }
}
