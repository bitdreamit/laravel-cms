<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Acme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domain\Dns\Services\AcmeClient::class;
    }
}
