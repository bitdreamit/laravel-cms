<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Asset;
use App\Models\Tenant\AssetContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::where('tenant_id', tenant('id'))
            ->with('container');

        if ($containerId = $request->input('container_id')) {
            $query->where('container_id', $containerId);
        }
        if ($folder = $request->input('folder')) {
            $query->where('folder', $folder);
        }
        if ($search = $request->input('search')) {
            $query->where('filename', 'like', "%{$search}%");
        }

        $assets = $query->orderByDesc('created_at')->paginate(24);
        return response()->json($assets);
    }

    public function store(Request $request)
    {
        $request->validate([
            'container_id' => 'required|uuid',
            'file' => 'required|file|max:51200', // 50MB max
            'folder' => 'nullable|string',
            'alt_text' => 'nullable|string',
            'title' => 'nullable|string',
        ]);

        $container = AssetContainer::where('tenant_id', tenant('id'))
            ->where('id', $request->input('container_id'))
            ->firstOrFail();

        $file = $request->file('file');
        $folder = $request->input('folder', '/');
        $filename = $file->getClientOriginalName();
        $path = $file->storeAs("assets/{$container->handle}{$folder}", $filename, $container->disk);

        $asset = Asset::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'container_id' => $container->id,
            'folder' => $folder,
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'alt_text' => $request->input('alt_text'),
            'title' => $request->input('title'),
            'uploaded_by' => auth()->id(),
        ]);

        // Get image dimensions if it's an image
        if ($asset->isImage()) {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $asset->update([
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ]);
            }
        }

        return response()->json($asset, 201);
    }

    public function show(string $id)
    {
        $asset = Asset::where('tenant_id', tenant('id'))->with('container')->findOrFail($id);
        return response()->json($asset);
    }

    public function update(Request $request, string $id)
    {
        $asset = Asset::where('tenant_id', tenant('id'))->findOrFail($id);
        $asset->update($request->only(['alt_text', 'title', 'focal_point', 'folder']));
        return response()->json($asset);
    }

    public function destroy(string $id)
    {
        $asset = Asset::where('tenant_id', tenant('id'))->findOrFail($id);
        // Delete file from storage
        \Illuminate\Support\Facades\Storage::disk($asset->container->disk)->delete($asset->path);
        $asset->delete();
        return response()->noContent();
    }

    public function containers()
    {
        $containers = AssetContainer::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($containers);
    }

    public function storeContainer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'disk' => 'required|string',
            'max_files' => 'integer|min:0',
            'allowed_file_types' => 'nullable|array',
        ]);

        $container = AssetContainer::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($container, 201);
    }
}
