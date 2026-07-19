<?php

namespace App\Domain\Content\Repositories\Interfaces;

interface CollectionRepositoryInterface
{
    public function find(string $id);
    public function findByHandle(string $handle);
    public function all(int $perPage = 15);
    public function create(array $data);
    public function update($collection, array $data);
    public function delete($collection): bool;
}
