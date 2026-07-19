<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCollectionRequest;
use App\Http\Requests\Admin\UpdateCollectionRequest;
use App\Http\Resources\Api\CollectionResource;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(protected CollectionRepositoryInterface $collections) {}

    public function index()
    {
        $collections = $this->collections->all();
        return CollectionResource::collection($collections);
    }

    public function store(StoreCollectionRequest $request)
    {
        $collection = $this->collections->create($request->validated());
        return new CollectionResource($collection);
    }

    public function show(string $id)
    {
        $collection = $this->collections->find($id);
        abort_unless($collection, 404);
        return new CollectionResource($collection);
    }

    public function update(UpdateCollectionRequest $request, string $id)
    {
        $collection = $this->collections->find($id);
        abort_unless($collection, 404);
        $collection = $this->collections->update($collection, $request->validated());
        return new CollectionResource($collection);
    }

    public function destroy(string $id)
    {
        $collection = $this->collections->find($id);
        abort_unless($collection, 404);
        $this->collections->delete($collection);
        return response()->noContent();
    }
}
