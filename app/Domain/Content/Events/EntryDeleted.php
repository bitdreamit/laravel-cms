<?php

namespace App\Domain\Content\Events;

use App\Models\Tenant\Entry;
use Illuminate\Foundation\Events\Dispatchable;

class EntryDeleted
{
    use Dispatchable;

    public function __construct(public Entry $entry) {}
}
