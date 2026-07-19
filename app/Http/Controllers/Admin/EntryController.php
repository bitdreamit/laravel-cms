<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Services\EntryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEntryRequest;
use App\Http\Requests\Admin\UpdateEntryRequest;
use App\Http\Resources\Api\EntryResource;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    public function __construct(protected EntryService $entryService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['collection_id', 'status', 'search', 'sort', 'direction']);
        $entries = $this->entryService->list($filters, (int) $request->input('per_page', 15));

        return EntryResource::collection($entries);
    }

    public function store(StoreEntryRequest $request)
    {
        $entry = $this->entryService->create($request->validated());
        return new EntryResource($entry);
    }

    public function show(string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);
        return new EntryResource($entry);
    }

    public function update(UpdateEntryRequest $request, string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);

        $entry = $this->entryService->update($entry, $request->validated());
        return new EntryResource($entry);
    }

    public function destroy(string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);
        $this->entryService->delete($entry);
        return response()->noContent();
    }

    public function publish(string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);
        $entry = $this->entryService->publish($entry);
        return new EntryResource($entry);
    }

    public function schedule(Request $request, string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);

        $data = $request->validate(['scheduled_at' => 'required|date|after:now']);
        $entry = $this->entryService->schedule($entry, $data['scheduled_at']);
        return new EntryResource($entry);
    }

    public function duplicate(string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);
        $newEntry = $this->entryService->duplicate($entry);
        return new EntryResource($newEntry);
    }

    public function restoreRevision(Request $request, string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);

        $data = $request->validate(['revision_id' => 'required|uuid']);
        $entry = $this->entryService->restoreRevision($entry, $data['revision_id']);
        return new EntryResource($entry);
    }

    public function revisions(string $id)
    {
        $entry = $this->entryService->get($id);
        abort_unless($entry, 404);

        return response()->json($entry->revisions()->orderByDesc('revision_number')->get());
    }
}
