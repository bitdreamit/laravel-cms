<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Taxonomy;
use App\Models\Tenant\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyController extends Controller
{
    public function index()
    {
        $taxonomies = Taxonomy::where('tenant_id', tenant('id'))->withCount('terms')->paginate(20);
        return response()->json($taxonomies);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_hierarchical' => 'boolean',
            'max_levels' => 'integer|min:1',
        ]);

        $taxonomy = Taxonomy::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($taxonomy, 201);
    }

    public function show(string $id)
    {
        $taxonomy = Taxonomy::where('tenant_id', tenant('id'))->with('terms')->findOrFail($id);
        return response()->json($taxonomy);
    }

    public function update(Request $request, string $id)
    {
        $taxonomy = Taxonomy::where('tenant_id', tenant('id'))->findOrFail($id);
        $taxonomy->update($request->only(['name', 'description', 'is_hierarchical', 'max_levels']));
        return response()->json($taxonomy);
    }

    public function destroy(string $id)
    {
        $taxonomy = Taxonomy::where('tenant_id', tenant('id'))->findOrFail($id);
        $taxonomy->delete();
        return response()->noContent();
    }

    public function terms(string $id)
    {
        $taxonomy = Taxonomy::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($taxonomy->terms);
    }

    public function storeTerm(Request $request, string $id)
    {
        $taxonomy = Taxonomy::where('tenant_id', tenant('id'))->findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $term = Term::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'taxonomy_id' => $taxonomy->id,
        ] + $data);

        return response()->json($term, 201);
    }
}
