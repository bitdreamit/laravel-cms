<?php

namespace App\Domain\Content\Events;

use App\Models\Tenant\Entry;
use Illuminate\Foundation\Events\Dispatchable;

class EntryCreated
{
    use Dispatchable;

    public function __construct(public Entry $entry) {}
}
