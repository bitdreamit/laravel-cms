<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Experiment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domain\Experiment\Services\ExperimentEngine::class;
    }
}
