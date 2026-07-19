<?php

namespace App\Domain\Theme\Events;

use App\Models\Central\Theme;
use Illuminate\Foundation\Events\Dispatchable;

class ThemeInstalled
{
    use Dispatchable;

    public function __construct(public Theme $theme) {}
}
