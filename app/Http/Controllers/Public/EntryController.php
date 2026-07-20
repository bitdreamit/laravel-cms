<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Entry;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    /**
     * Home page — if the current domain has a default_collection_handle,
     * show that collection's index instead of the home page.
     */
    public function home()
    {
        // Safely get the current domain — may be null on central/localhost
        $domain = app()->bound('current.domain') ? app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null : null;

        if ($domain && $domain->default_collection_handle) {
            return $this->collectionIndex();
        }

        return view('public.home');
    }

    /**
     * Collection index — lists entries in a collection.
     * If no handle is provided, uses the domain's default_collection_handle.
     */
    public function collectionIndex(string $collectionHandle = null)
    {
        $domain = app()->bound('current.domain') ? app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null : null;

        // If no handle passed, try to get from domain config
        if (! $collectionHandle) {
            $collectionHandle = $domain?->default_collection_handle;
        }

        if (! $collectionHandle) {
            abort(404);
        }

        $collection = Collection::where('tenant_id', tenant('id'))
            ->where('handle', $collectionHandle)
            ->firstOrFail();

        $entries = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('public.collection-index', [
            'collection' => $collection,
            'entries' => $entries,
        ]);
    }

    /**
     * Show a single entry.
     * Handles both subdomain mode (/{slug}) and standard mode (/{collection}/{slug}).
     */
    public function collectionShow(Request $request, string $param1, ?string $param2 = null)
    {
        $domain = app()->bound('current.domain') ? app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null : null;

        // Subdomain mode: /{slug} → entry in default_collection_handle
        if ($domain && $domain->default_collection_handle && $param2 === null) {
            $handle = $domain->default_collection_handle;
            $slug = $param1;
        } else {
            // Standard mode: /{collectionHandle}/{slug}
            $handle = $param1;
            $slug = $param2;
        }

        if (! $handle || ! $slug) {
            abort(404);
        }

        $collection = Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $entry = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('public.entry-show', [
            'collection' => $collection,
            'entry' => $entry,
        ]);
    }

    /**
     * Show entries tagged with a specific term.
     */
    public function collectionTerm(Request $request, string $term)
    {
        $domain = app()->bound('current.domain') ? app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null : null;
        $handle = $domain?->default_collection_handle;

        if (! $handle) {
            // If no default collection, try getting from route parameter
            $handle = $request->route('collectionHandle');
        }

        if (! $handle) {
            abort(404);
        }

        $collection = Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $termModel = \App\Models\Tenant\Term::where('tenant_id', tenant('id'))
            ->where('slug', $term)
            ->firstOrFail();

        $entries = $termModel->entries()
            ->where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('status', 'published')
            ->paginate(12);

        return view('public.collection-term', [
            'collection' => $collection,
            'term' => $termModel,
            'entries' => $entries,
        ]);
    }
}
