<?php

namespace App\Modules\Catalog\ProductImages;

use App\Modules\Catalog\Exceptions\ProductImageStorageFailed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageStorage
{
    private const string DIRECTORY = 'products';

    public function store(UploadedFile $image): string
    {
        $disk = $this->diskName();
        $path = $image->store($this->directory(), $disk);

        if ($path === false) {
            throw new ProductImageStorageFailed(__('general.errors.product_image_storage_failed'));
        }

        return Storage::disk($disk)->url($path);
    }

    public function deleteIfOwned(?string $publicUrl): void
    {
        $path = $this->pathFromPublicUrl($publicUrl, $this->diskName());

        if ($path === null) {
            return;
        }

        Storage::disk($this->diskName())->delete($path);
    }

    public function deleteReplaced(?string $previousPublicUrl, string $newPublicUrl): void
    {
        if ($previousPublicUrl === null || $previousPublicUrl === $newPublicUrl) {
            return;
        }

        $this->deleteIfOwned($previousPublicUrl);
    }

    private function pathFromPublicUrl(?string $publicUrl, string $disk): ?string
    {
        if ($publicUrl === null) {
            return null;
        }

        return $this->pathFromCurrentDiskUrl($publicUrl, $disk)
            ?? $this->pathFromLegacyPublicStorageUrl($publicUrl);
    }

    private function pathFromCurrentDiskUrl(string $publicUrl, string $disk): ?string
    {
        $probePath = $this->directory().'/.product-image-storage-probe';

        try {
            $probeUrl = Storage::disk($disk)->url($probePath);
        } catch (Throwable) {
            return null;
        }

        $publicUrlParts = parse_url($publicUrl);
        $probeUrlParts = parse_url($probeUrl);

        if (!is_array($publicUrlParts) || !is_array($probeUrlParts)) {
            return null;
        }

        if (!$this->hasSameOrigin($publicUrlParts, $probeUrlParts)) {
            return null;
        }

        $publicPath = $this->decodedPathFromUrlParts($publicUrlParts);
        $probeUrlPath = $this->decodedPathFromUrlParts($probeUrlParts);

        if ($publicPath === null || $probeUrlPath === null || !str_ends_with($probeUrlPath, $probePath)) {
            return null;
        }

        $basePath = substr($probeUrlPath, 0, -strlen($probePath));

        if (!str_starts_with($publicPath, $basePath)) {
            return null;
        }

        return $this->ownedPath(substr($publicPath, strlen($basePath)));
    }

    private function pathFromLegacyPublicStorageUrl(string $publicUrl): ?string
    {
        if (!str_starts_with($publicUrl, '/storage/') && !str_starts_with($publicUrl, 'storage/')) {
            return null;
        }

        $path = parse_url($publicUrl, PHP_URL_PATH);

        if (!is_string($path)) {
            return null;
        }

        $storagePath = rawurldecode(ltrim($path, '/'));
        $publicPrefix = 'storage/';

        if (!str_starts_with($storagePath, $publicPrefix)) {
            return null;
        }

        return $this->ownedPath(substr($storagePath, strlen($publicPrefix)));
    }

    /**
     * @param  array<string, int|string>  $publicUrlParts
     * @param  array<string, int|string>  $probeUrlParts
     */
    private function hasSameOrigin(array $publicUrlParts, array $probeUrlParts): bool
    {
        foreach (['scheme', 'host', 'port'] as $key) {
            if (($publicUrlParts[$key] ?? null) !== ($probeUrlParts[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int|string>  $urlParts
     */
    private function decodedPathFromUrlParts(array $urlParts): ?string
    {
        if (!isset($urlParts['path']) || !is_string($urlParts['path'])) {
            return null;
        }

        return rawurldecode(ltrim($urlParts['path'], '/'));
    }

    private function ownedPath(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (
            $path === ''
            || str_contains($path, "\0")
            || str_contains($path, '\\')
        ) {
            return null;
        }

        $segments = explode('/', $path);

        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            return null;
        }

        $directory = $this->directory();

        if (!str_starts_with($path, $directory.'/')) {
            return null;
        }

        return $path;
    }

    private function diskName(): string
    {
        return (string) config('catalog.product_images.disk', 'public');
    }

    private function directory(): string
    {
        $directory = trim((string) config('catalog.product_images.directory', self::DIRECTORY), '/');

        return $directory !== '' ? $directory : self::DIRECTORY;
    }
}
