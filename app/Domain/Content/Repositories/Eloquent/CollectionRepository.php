<?php

namespace App\Domain\Content\Repositories\Eloquent;

use App\Domain\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Models\Tenant\Collection;
use Illuminate\Support\Str;

class CollectionRepository implements CollectionRepositoryInterface
{
    public function find(string $id)
    {
        return Collection::where('tenant_id', tenant('id'))->where('id', $id)->first();
    }

    public function findByHandle(string $handle)
    {
        return Collection::where('tenant_id', tenant('id'))->where('handle', $handle)->first();
    }

    public function all(int $perPage = 15)
    {
        return Collection::where('tenant_id', tenant('id'))->orderBy('sort_order')->paginate($perPage);
    }

    public function create(array $data)
    {
        return Collection::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));
    }

    public function update($collection, array $data)
    {
        $collection->update($data);
        return $collection->fresh();
    }

    public function delete($collection): bool
    {
        return $collection->delete();
    }
}
