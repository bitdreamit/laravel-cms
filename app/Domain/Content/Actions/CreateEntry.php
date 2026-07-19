<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Domain\Content\Events\EntryCreated;
use Illuminate\Support\Str;

class CreateEntry
{
    public function __construct(protected EntryRepositoryInterface $entries) {}

    public function execute(array $data): \App\Models\Tenant\Entry
    {
        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        }

        $entry = $this->entries->create($data);

        event(new EntryCreated($entry));

        return $entry;
    }
}
