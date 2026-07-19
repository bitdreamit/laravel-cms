<?php

namespace App\Domain\Theme\Listeners;

use App\Domain\Theme\Events\ThemeActivated;
use App\Domain\Theme\Services\ThemeVariableCompiler;
use Illuminate\Support\Facades\Cache;

class ClearThemeCache
{
    public function __construct(protected ThemeVariableCompiler $compiler) {}

    public function handle(ThemeActivated $event): void
    {
        Cache::forget("theme:{$event->tenant->id}:css-vars");
        Cache::forget("theme:{$event->tenant->id}:settings");
    }
}
