<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

class CmsConnector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domain\Connector\Services\ConnectorManager::class;
    }
}
