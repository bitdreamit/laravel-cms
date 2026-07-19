<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EntryResource;
use App\Models\Tenant\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EntryController extends Controller
{
    public function index(Request $request, string $handle)
    {
        $collection = \App\Models\Tenant\Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $query = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id);

        // Public API: only published entries
        if (! $request->user()?->tokenCan('read-drafts')) {
            $query->where('status', 'published')->where('published_at', '<=', now());
        }

        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $entries = $query->orderByDesc('published_at')->paginate($perPage);

        return EntryResource::collection($entries);
    }

    public function show(Request $request, string $handle, string $slug)
    {
        $collection = \App\Models\Tenant\Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $entry = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('slug', $slug)
            ->firstOrFail();

        // Public API: published only
        if (! $request->user()?->tokenCan('read-drafts') && ! $entry->isPublished()) {
            abort(404);
        }

        return new EntryResource($entry);
    }

    public function store(Request $request, string $handle)
    {
        $collection = \App\Models\Tenant\Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'status' => 'in:draft,published,scheduled',
            'data' => 'array',
            'published_at' => 'nullable|date',
            'template' => 'nullable|string',
        ]);

        $entry = Entry::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'collection_id' => $collection->id,
            'created_by' => $request->user()?->id,
        ]));

        return new EntryResource($entry);
    }

    public function update(Request $request, string $handle, string $slug)
    {
        $collection = \App\Models\Tenant\Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $entry = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $request->validate([
            'title' => 'string|max:255',
            'slug' => 'string|max:255',
            'status' => 'in:draft,published,scheduled',
            'data' => 'array',
            'published_at' => 'nullable|date',
            'template' => 'nullable|string',
        ]);

        $entry->update($data);

        return new EntryResource($entry->fresh());
    }

    public function destroy(string $handle, string $slug)
    {
        $collection = \App\Models\Tenant\Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $entry = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $entry->delete();
        return response()->noContent();
    }
}
