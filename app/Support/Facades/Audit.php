<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Audit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domain\Audit\Services\AuditStreamManager::class;
    }
}
