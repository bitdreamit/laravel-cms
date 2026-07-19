<?php

namespace App\Domain\Theme\Events;

use App\Models\Central\Theme;
use App\Models\Central\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

class ThemeActivated
{
    use Dispatchable;

    public function __construct(public Theme $theme, public Tenant $tenant) {}
}
