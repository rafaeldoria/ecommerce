<?php

namespace App\Modules\Catalog\ProductImages;

use App\Modules\Catalog\Exceptions\ProductImageStorageFailed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageStorage
{
    private const string DIRECTORY = 'products';

    public function store(UploadedFile $image): string
    {
        $path = $image->store(self::DIRECTORY, 'public');

        if ($path === false) {
            throw new ProductImageStorageFailed(__('general.errors.product_image_storage_failed'));
        }

        return Storage::disk('public')->url($path);
    }

    public function deleteIfOwned(?string $publicUrl): void
    {
        $path = $this->pathFromPublicUrl($publicUrl);

        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function deleteReplaced(?string $previousPublicUrl, string $newPublicUrl): void
    {
        if ($previousPublicUrl === null || $previousPublicUrl === $newPublicUrl) {
            return;
        }

        $this->deleteIfOwned($previousPublicUrl);
    }

    private function pathFromPublicUrl(?string $publicUrl): ?string
    {
        if ($publicUrl === null) {
            return null;
        }

        $path = parse_url($publicUrl, PHP_URL_PATH);

        if (!is_string($path)) {
            return null;
        }

        $storagePath = ltrim($path, '/');
        $publicPrefix = 'storage/';

        if (!str_starts_with($storagePath, $publicPrefix)) {
            return null;
        }

        $diskPath = rawurldecode(substr($storagePath, strlen($publicPrefix)));

        if (!str_starts_with($diskPath, self::DIRECTORY.'/')) {
            return null;
        }

        return $diskPath;
    }
}
