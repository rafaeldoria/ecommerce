<?php

namespace Tests\Feature\Api\Admin;

use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\ProductImageStorageFailed;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Catalog\ProductImages\ProductImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_manage_products_and_see_unavailable_items(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $unavailableProduct = Product::factory()->create([
            'name' => 'Unavailable Arcana',
            'quantity' => 0,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->getJson('/api/admin/products')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.products.listed'))
            ->assertJsonFragment(['name' => 'Unavailable Arcana'])
            ->assertJsonFragment(['quantity' => 0]);

        $createdResponse = $this->post('/api/admin/products', [
            'name' => 'Phantom Assassin Arcana',
            'image' => $this->fakePngUpload('pa.png'),
            'quantity' => 2,
            'price' => 159900,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json']);

        $createdResponse
            ->assertCreated()
            ->assertJsonPath('message', __('general.api.admin.products.created'))
            ->assertJsonPath('data.game.id', $game->getKey())
            ->assertJsonPath('data.rarity.id', $rarity->getKey());

        $createdProductId = $createdResponse->json('data.id');
        $createdImageUrl = (string) $createdResponse->json('data.image_url');

        $this->assertStringContainsString('/storage/products/', $createdImageUrl);
        Storage::disk('public')->assertExists($this->storagePathFromPublicUrl($createdImageUrl));

        $this->getJson("/api/admin/products/{$unavailableProduct->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $unavailableProduct->getKey());

        $this->patch("/api/admin/products/{$createdProductId}", [
            'name' => 'Phantom Assassin Arcana Updated',
            'image' => $this->fakePngUpload('pa-updated.png'),
            'quantity' => 4,
            'price' => 169900,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.products.updated'))
            ->assertJsonPath('data.quantity', 4)
            ->assertJsonPath('data.price', 169900);

        $updatedProduct = Product::query()->findOrFail($createdProductId);

        $this->assertStringContainsString('/storage/products/', $updatedProduct->url_img);
        $this->assertNotSame($createdImageUrl, $updatedProduct->url_img);
        Storage::disk('public')->assertExists($this->storagePathFromPublicUrl($updatedProduct->url_img));
        Storage::disk('public')->assertMissing($this->storagePathFromPublicUrl($createdImageUrl));

        $this->deleteJson("/api/admin/products/{$createdProductId}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.products.deleted'));

        $this->assertSoftDeleted('products', ['id' => $createdProductId]);
    }

    #[Test]
    public function product_writes_validate_references_and_numeric_rules(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $game->delete();
        $rarity = Rarity::factory()->create();
        $rarity->delete();

        $response = $this->post('/api/admin/products', [
            'name' => 'Broken Product',
            'image' => $this->fakePngUpload('item.png'),
            'quantity' => -1,
            'price' => -20,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json']);

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );

        $response
            ->assertJsonValidationErrors(['quantity', 'price', 'game_id', 'rarity_id']);
    }

    #[Test]
    public function product_create_requires_valid_image_upload(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        $response = $this->post('/api/admin/products', [
            'name' => 'Invalid Image Product',
            'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            'quantity' => 1,
            'price' => 1000,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json']);

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );

        $response
            ->assertJsonValidationErrors(['image']);
    }

    #[Test]
    public function product_create_returns_problem_details_when_image_storage_fails(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        $this->app->instance(ProductImageStorage::class, new class extends ProductImageStorage
        {
            public function store(UploadedFile $image): string
            {
                throw new ProductImageStorageFailed(__('general.errors.product_image_storage_failed'));
            }
        });

        $response = $this->post('/api/admin/products', [
            'name' => 'Image Failure Product',
            'image' => $this->fakePngUpload('failed-storage.png'),
            'quantity' => 1,
            'price' => 1000,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json']);

        $this->assertProblemDetails(
            $response,
            'product_image_storage_failed',
            500,
            __('general.errors.product_image_storage_failed'),
        );
    }

    #[Test]
    public function product_update_keeps_existing_image_when_no_new_image_is_sent(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Storage::disk('public')->put('products/existing.png', 'existing image');
        $product = Product::factory()->create([
            'url_img' => '/storage/products/existing.png',
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->patchJson("/api/admin/products/{$product->getKey()}", [
            'name' => 'Image Kept Product',
            'quantity' => 3,
            'price' => 2000,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ])
            ->assertOk()
            ->assertJsonPath('data.image_url', '/storage/products/existing.png');

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'url_img' => '/storage/products/existing.png',
        ]);
        Storage::disk('public')->assertExists('products/existing.png');
    }

    #[Test]
    public function product_create_cleans_up_uploaded_image_when_persistence_fails(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        $this->app->instance(CreateProductAction::class, new class extends CreateProductAction
        {
            public function __construct() {}

            public function execute(CreateProductData $data): Product
            {
                throw new InvalidProductData(__('general.errors.invalid_product_price'));
            }
        });

        $response = $this->post('/api/admin/products', [
            'name' => 'Failed Product',
            'image' => $this->fakePngUpload('failed.png'),
            'quantity' => 1,
            'price' => 1000,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json']);

        $this->assertProblemDetails(
            $response,
            'invalid_product_data',
            422,
            __('general.errors.invalid_product_price'),
        );

        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    #[Test]
    public function product_update_cleans_up_new_image_when_persistence_fails(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Storage::disk('public')->put('products/existing.png', 'existing image');
        $product = Product::factory()->create([
            'url_img' => '/storage/products/existing.png',
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->app->instance(UpdateProductAction::class, new class extends UpdateProductAction
        {
            public function __construct() {}

            public function execute(int $productId, UpdateProductData $data): Product
            {
                throw new InvalidProductData(__('general.errors.invalid_product_quantity'));
            }
        });

        $response = $this->patch("/api/admin/products/{$product->getKey()}", [
            'name' => 'Failed Product Update',
            'image' => $this->fakePngUpload('failed-update.png'),
            'quantity' => 1,
            'price' => 1000,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ], ['Accept' => 'application/json']);

        $this->assertProblemDetails(
            $response,
            'invalid_product_data',
            422,
            __('general.errors.invalid_product_quantity'),
        );

        Storage::disk('public')->assertExists('products/existing.png');
        $this->assertSame(['products/existing.png'], Storage::disk('public')->allFiles('products'));
    }

    #[Test]
    public function public_catalog_keeps_hiding_zero_quantity_products(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Product::factory()->create([
            'name' => 'Hidden Product',
            'quantity' => 0,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->getJson('/api/catalog/products')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Hidden Product']);
    }

    #[Test]
    public function non_numeric_product_routes_return_not_found(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/products/not-a-number');

        $this->assertProblemDetails(
            $response,
            'resource_not_found',
            404,
            __('general.api.errors.resource_not_found'),
        );
    }

    #[Test]
    public function anonymous_users_cannot_access_admin_products(): void
    {
        $response = $this->getJson('/api/admin/products');

        $this->assertProblemDetails(
            $response,
            'unauthenticated',
            401,
            __('general.api.errors.unauthenticated'),
        );
    }

    #[Test]
    public function customers_cannot_access_admin_products(): void
    {
        $this->actingAsCustomer();

        $response = $this->getJson('/api/admin/products');

        $this->assertProblemDetails(
            $response,
            'forbidden',
            403,
            __('general.api.errors.forbidden'),
        );
    }

    private function storagePathFromPublicUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        $this->assertIsString($path);

        return ltrim(str_replace('/storage/', '', $path), '/');
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        ));
    }
}
