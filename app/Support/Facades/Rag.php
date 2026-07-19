<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Domain\Rag\DTOs\RagResponse ask(string $tenantId, string $question, ?string $userId = null)
 * @method static void indexEntry(\App\Models\Tenant\Entry $entry)
 * @method static void removeFromIndex(\App\Models\Tenant\Entry $entry)
 */
class Rag extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domain\Rag\Services\RagService::class;
    }
}
