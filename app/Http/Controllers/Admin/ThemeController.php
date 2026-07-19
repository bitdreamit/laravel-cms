<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Central\Theme;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function index()
    {
        $themes = Theme::orderBy('name')->paginate(20);
        return response()->json($themes);
    }

    public function show(string $id)
    {
        $theme = Theme::with('parent')->findOrFail($id);
        return response()->json($theme);
    }

    public function activate(Request $request, string $id)
    {
        $theme = Theme::findOrFail($id);
        $tenant = tenant();
        $tenant->update(['current_theme_id' => $theme->id]);

        return response()->json([
            'message' => "Theme '{$theme->name}' activated.",
            'theme_id' => $theme->id,
        ]);
    }

    public function updateSettings(Request $request, string $id)
    {
        $theme = Theme::findOrFail($id);

        $data = $request->validate([
            'settings' => 'required|array',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
        ]);

        // Save theme customization for current tenant
        \App\Models\Tenant\ThemeCustomization::updateOrCreate(
            [
                'tenant_id' => tenant('id'),
                'theme_id' => $theme->id,
            ],
            [
                'settings' => $data['settings'],
                'custom_css' => $data['custom_css'] ?? null,
                'custom_js' => $data['custom_js'] ?? null,
            ]
        );

        return response()->json(['message' => 'Theme settings updated.']);
    }
}
