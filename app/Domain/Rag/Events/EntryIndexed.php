<?php

namespace App\Domain\Rag\Events;

use App\Models\Tenant\Entry;
use Illuminate\Foundation\Events\Dispatchable;

class EntryIndexed
{
    use Dispatchable;

    public function __construct(public Entry $entry) {}
}
