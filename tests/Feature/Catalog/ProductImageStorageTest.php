<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\ProductImages\ProductImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImageStorageTest extends TestCase
{
    #[Test]
    public function it_stores_product_images_on_the_configured_disk_and_directory(): void
    {
        $this->fakeProductImageDisk();
        Storage::fake('public');

        $url = app(ProductImageStorage::class)->store($this->fakePngUpload('item.png'));

        $this->assertStringStartsWith('https://images.example.test/assets/catalog/products/', $url);
        $this->assertCount(1, Storage::disk('product-images')->allFiles('catalog/products'));
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    #[Test]
    public function it_deletes_owned_urls_generated_by_the_configured_disk(): void
    {
        $this->fakeProductImageDisk();
        Storage::disk('product-images')->put('catalog/products/old.png', 'old image');
        Storage::disk('product-images')->put('catalog/products/new.png', 'new image');

        app(ProductImageStorage::class)->deleteReplaced(
            'https://images.example.test/assets/catalog/products/old.png',
            'https://images.example.test/assets/catalog/products/new.png',
        );

        Storage::disk('product-images')->assertMissing('catalog/products/old.png');
        Storage::disk('product-images')->assertExists('catalog/products/new.png');
    }

    #[Test]
    public function it_deletes_legacy_public_storage_urls_when_the_public_disk_is_configured(): void
    {
        config([
            'catalog.product_images.disk' => 'public',
            'catalog.product_images.directory' => 'products',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('products/legacy.png', 'legacy image');

        app(ProductImageStorage::class)->deleteIfOwned('/storage/products/legacy.png');

        Storage::disk('public')->assertMissing('products/legacy.png');
    }

    #[Test]
    public function it_deletes_absolute_legacy_public_storage_urls_from_the_public_disk_after_s3_migration(): void
    {
        config([
            'catalog.product_images.disk' => 'product-images',
            'catalog.product_images.directory' => 'products',
        ]);
        Storage::fake('product-images', [
            'url' => 'https://images.example.test',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('products/legacy.png', 'legacy image');
        Storage::disk('product-images')->put('products/legacy.png', 's3 image with same key');

        app(ProductImageStorage::class)->deleteIfOwned('https://shop.example.test/storage/products/legacy.png');

        Storage::disk('public')->assertMissing('products/legacy.png');
        Storage::disk('product-images')->assertExists('products/legacy.png');
    }

    #[Test]
    public function it_ignores_unknown_urls_and_paths_outside_the_product_image_directory(): void
    {
        $this->fakeProductImageDisk();
        Storage::disk('product-images')->put('catalog/products/owned.png', 'owned image');
        Storage::disk('product-images')->put('catalog/other/foreign.png', 'foreign image');

        $storage = app(ProductImageStorage::class);

        $storage->deleteIfOwned('https://other.example.test/assets/catalog/products/owned.png');
        $storage->deleteIfOwned('https://images.example.test/assets/catalog/other/foreign.png');
        $storage->deleteIfOwned('https://images.example.test/assets/catalog/products/../other/foreign.png');

        Storage::disk('product-images')->assertExists('catalog/products/owned.png');
        Storage::disk('product-images')->assertExists('catalog/other/foreign.png');
    }

    private function fakeProductImageDisk(): void
    {
        config([
            'catalog.product_images.disk' => 'product-images',
            'catalog.product_images.directory' => 'catalog/products',
        ]);

        Storage::fake('product-images', [
            'url' => 'https://images.example.test/assets',
        ]);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        ));
    }
}
