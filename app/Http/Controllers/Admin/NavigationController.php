<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Navigation;
use App\Models\Tenant\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NavigationController extends Controller
{
    public function index()
    {
        $navigations = Navigation::where('tenant_id', tenant('id'))->with('items')->paginate(20);
        return response()->json($navigations);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'max_depth' => 'integer|min:1|max:10',
        ]);

        $nav = Navigation::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($nav, 201);
    }

    public function show(string $id)
    {
        $nav = Navigation::where('tenant_id', tenant('id'))->with('items')->findOrFail($id);
        return response()->json($nav);
    }

    public function update(Request $request, string $id)
    {
        $nav = Navigation::where('tenant_id', tenant('id'))->findOrFail($id);
        $nav->update($request->only(['name', 'max_depth']));
        return response()->json($nav);
    }

    public function destroy(string $id)
    {
        $nav = Navigation::where('tenant_id', tenant('id'))->findOrFail($id);
        $nav->delete();
        return response()->noContent();
    }

    public function storeItem(Request $request, string $id)
    {
        $nav = Navigation::where('tenant_id', tenant('id'))->findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'url' => 'nullable|string',
            'entry_id' => 'nullable|uuid',
            'parent_id' => 'nullable|uuid',
            'target' => 'in:_self,_blank',
            'sort_order' => 'integer',
        ]);

        $item = NavigationItem::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'navigation_id' => $nav->id,
        ] + $data);

        return response()->json($item, 201);
    }

    public function updateItem(Request $request, string $id, string $itemId)
    {
        $item = NavigationItem::where('tenant_id', tenant('id'))
            ->where('navigation_id', $id)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->update($request->all());
        return response()->json($item);
    }

    public function destroyItem(string $id, string $itemId)
    {
        $item = NavigationItem::where('tenant_id', tenant('id'))
            ->where('navigation_id', $id)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->delete();
        return response()->noContent();
    }
}
