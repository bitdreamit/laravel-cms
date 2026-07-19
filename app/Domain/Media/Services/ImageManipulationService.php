<?php

namespace App\Domain\Media\Services;

use App\Models\Tenant\Asset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageManipulationService
{
    public function __construct(protected ImageManager $imageManager) {}

    /**
     * Generate image variant with on-the-fly manipulation.
     * URL format: /img/{asset_id}/{params}/filename.jpg
     *
     * Params (URL path):
     *   w=800        Width
     *   h=600        Height
     *   fit=crop     Fit mode: crop, max, stretch, contain
     *   q=80         Quality (1-100)
     *   fm=webp      Format: webp, jpg, png, gif
     *   blur=10      Blur radius
     *   sharp=5      Sharpen amount
     *   gray=1       Grayscale
     *   flip=h       Flip: h, v, both
     *   rot=90       Rotate degrees
     *   bg=ffffff    Background color (for contain)
     *   dpr=2        Device pixel ratio
     */
    public function process(Asset $asset, array $params): array
    {
        $cacheKey = "img:{$asset->id}:" . md5(json_encode($params));

        return Cache::remember($cacheKey, 86400, function () use ($asset, $params) {
            $sourcePath = Storage::disk($asset->container->disk)->path($asset->path);
            if (! File::exists($sourcePath)) {
                throw new \RuntimeException("Source image not found: {$asset->path}");
            }

            $image = $this->imageManager->make($sourcePath);

            // Apply manipulations
            $image = $this->applyResize($image, $params);
            $image = $this->applyFilters($image, $params);
            $image = $this->applyTransforms($image, $params);

            // Encode
            $format = $params['fm'] ?? 'jpg';
            $quality = (int) ($params['q'] ?? 80);

            $encoded = $image->encode($format, $quality);

            // Store in cache directory
            $cacheDir = storage_path('app/public/img-cache');
            File::makeDirectory($cacheDir, 0755, true, true);

            $filename = $this->generateFilename($asset, $params, $format);
            $cachePath = "{$cacheDir}/{$filename}";
            File::put($cachePath, $encoded);

            return [
                'path' => $cachePath,
                'url' => "/storage/img-cache/{$filename}",
                'mime_type' => $this->getMimeType($format),
                'size' => strlen($encoded),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
            ];
        });
    }

    public function getUrl(Asset $asset, array $params = []): string
    {
        $defaults = ['w' => null, 'h' => null, 'q' => 80, 'fm' => 'jpg'];
        $params = array_merge($defaults, $params);

        // Check if cached version exists
        $cacheKey = "img:{$asset->id}:" . md5(json_encode($params));
        $cached = Cache::get($cacheKey);

        if ($cached) return $cached['url'];

        // Generate URL that triggers on-demand processing
        $paramStr = http_build_query(array_filter($params));
        return "/img/{$asset->id}?{$paramStr}";
    }

    public function getPresets(): array
    {
        return config('media.image_manipulation.presets', [
            'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
            'medium' => ['w' => 600, 'fit' => 'max'],
            'large' => ['w' => 1200, 'fit' => 'max'],
            'social' => ['w' => 1200, 'h' => 630, 'fit' => 'crop'],
        ]);
    }

    public function getPresetUrl(Asset $asset, string $preset): string
    {
        $presets = $this->getPresets();
        if (! isset($presets[$preset])) {
            throw new \InvalidArgumentException("Unknown preset: {$preset}");
        }

        return $this->getUrl($asset, $presets[$preset]);
    }

    public function clearCache(?Asset $asset = null): void
    {
        if ($asset) {
            // Clear cache for a specific asset
            $cacheDir = storage_path('app/public/img-cache');
            $pattern = "{$asset->id}-*";
            foreach (glob("{$cacheDir}/{$pattern}") as $file) {
                File::delete($file);
            }
        } else {
            // Clear entire image cache
            $cacheDir = storage_path('app/public/img-cache');
            if (File::isDirectory($cacheDir)) {
                File::cleanDirectory($cacheDir);
            }
        }
    }

    protected function applyResize($image, array $params)
    {
        $width = isset($params['w']) ? (int) $params['w'] : null;
        $height = isset($params['h']) ? (int) $params['h'] : null;
        $fit = $params['fit'] ?? 'max';
        $dpr = (float) ($params['dpr'] ?? 1);

        if ($dpr > 1) {
            $width = $width ? (int) ($width * $dpr) : null;
            $height = $height ? (int) ($height * $dpr) : null;
        }

        if (! $width && ! $height) return $image;

        return match($fit) {
            'crop' => $image->fit($width ?? $height, $height ?? $width),
            'max' => $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }),
            'stretch' => $image->resize($width, $height),
            'contain' => $image->resizeCanvas($width, $height, $params['bg'] ?? 'ffffff', true),
            default => $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }),
        };
    }

    protected function applyFilters($image, array $params)
    {
        if (isset($params['blur'])) {
            $image->blur((int) $params['blur']);
        }

        if (isset($params['sharp'])) {
            $image->sharpen((int) $params['sharp']);
        }

        if (isset($params['gray']) && $params['gray']) {
            $image->greyscale();
        }

        if (isset($params['bg'])) {
            $image->resizeCanvas($image->getWidth(), $image->getHeight(), $params['bg'], true);
        }

        return $image;
    }

    protected function applyTransforms($image, array $params)
    {
        if (isset($params['flip'])) {
            match($params['flip']) {
                'h' => $image->flip('h'),
                'v' => $image->flip('v'),
                'both' => $image->flip('h')->flip('v'),
                default => null,
            };
        }

        if (isset($params['rot'])) {
            $image->rotate((int) $params['rot']);
        }

        return $image;
    }

    protected function generateFilename(Asset $asset, array $params, string $format): string
    {
        $hash = md5(json_encode($params));
        $baseName = pathinfo($asset->filename, PATHINFO_FILENAME);
        return "{$asset->id}-{$baseName}-{$hash}.{$format}";
    }

    protected function getMimeType(string $format): string
    {
        return match($format) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
