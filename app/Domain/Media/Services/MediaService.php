<?php

namespace App\Domain\Media\Services;

use App\Models\Tenant\Asset;
use App\Models\Tenant\AssetContainer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaService
{
    public function upload(AssetContainer $container, UploadedFile $file, string $folder = '/', array $meta = []): Asset
    {
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
            'alt_text' => $meta['alt_text'] ?? null,
            'title' => $meta['title'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        if ($asset->isImage()) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo) {
                $asset->update(['width' => $imageInfo[0], 'height' => $imageInfo[1]]);
            }
        }

        return $asset;
    }

    public function generateVariant(Asset $asset, array $params): string
    {
        // Generate image variant (resize, crop, etc.)
        // In production, use intervention/image or a queue-based image processor
        $paramsStr = http_build_query($params);
        return "/img/{$asset->id}?{$paramsStr}";
    }

    public function delete(Asset $asset): bool
    {
        \Illuminate\Support\Facades\Storage::disk($asset->container->disk)->delete($asset->path);
        return $asset->delete();
    }

    public function getSignedUrl(Asset $asset, int $expiresMinutes = 60): string
    {
        return \Illuminate\Support\Facades\Storage::disk($asset->container->disk)
            ->temporaryUrl($asset->path, now()->addMinutes($expiresMinutes));
    }
}
