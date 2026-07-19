<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Entry;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    public function home()
    {
        $domain = app('current.domain');
        if ($domain && $domain->default_collection_handle) {
            return $this->collectionIndex($domain->default_collection_handle);
        }
        return view('public.home');
    }

    public function collectionIndex(string $collectionHandle = null)
    {
        $domain = app('current.domain');
        $handle = $collectionHandle ?? $domain?->default_collection_handle;

        if (! $handle) abort(404);

        $collection = Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
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

    public function collectionShow(string $collectionHandle, string $slug = null)
    {
        // If only one parameter is passed, the URL is /{slug} and we look it up by collection
        $domain = app('current.domain');

        if ($domain && $domain->default_collection_handle) {
            // Subdomain mode: /{slug} → entry in default collection
            $actualSlug = $collectionHandle;
            $handle = $domain->default_collection_handle;
        } else {
            $actualSlug = $slug;
            $handle = $collectionHandle;
        }

        $collection = Collection::where('tenant_id', tenant('id'))
            ->where('handle', $handle)
            ->firstOrFail();

        $entry = Entry::where('tenant_id', tenant('id'))
            ->where('collection_id', $collection->id)
            ->where('slug', $actualSlug)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('public.entry-show', [
            'collection' => $collection,
            'entry' => $entry,
        ]);
    }

    public function collectionTerm(string $term)
    {
        $domain = app('current.domain');
        $handle = $domain?->default_collection_handle;
        if (! $handle) abort(404);

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
