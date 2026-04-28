<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Products;
use App\Livewire\Admin\Products\Edit as EditProduct;
use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductsCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_update_and_delete_products_from_the_web_page(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);

        Livewire::actingAs($admin)
            ->test(Products::class)
            ->call('beginCreate')
            ->set('name', 'Phantom Assassin Arcana')
            ->set('image', $this->fakePngUpload('pa.png'))
            ->set('quantity', 2)
            ->set('price', 159900)
            ->set('game_id', $game->getKey())
            ->set('rarity_id', $rarity->getKey())
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee(__('admin.products.messages.created'))
            ->assertSet('isFormOpen', false);

        $product = Product::query()->where('name', 'Phantom Assassin Arcana')->firstOrFail();
        $createdImageUrl = $product->url_img;

        $this->assertStringContainsString('/storage/products/', $createdImageUrl);
        Storage::disk('public')->assertExists($this->storagePathFromPublicUrl($createdImageUrl));

        Livewire::actingAs($admin)
            ->test(EditProduct::class, ['product' => $product])
            ->assertSet('name', 'Phantom Assassin Arcana')
            ->set('name', 'Phantom Assassin Arcana Updated')
            ->set('image', $this->fakePngUpload('pa-updated.png'))
            ->set('quantity', 4)
            ->set('price', 169900)
            ->set('game_id', $game->getKey())
            ->set('rarity_id', $rarity->getKey())
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('Phantom Assassin Arcana Updated', $product->name);
        $this->assertSame(4, $product->quantity);
        $this->assertSame(169900, $product->price);
        $this->assertNotSame($createdImageUrl, $product->url_img);
        Storage::disk('public')->assertExists($this->storagePathFromPublicUrl($product->url_img));
        Storage::disk('public')->assertMissing($this->storagePathFromPublicUrl($createdImageUrl));

        Livewire::actingAs($admin)
            ->test(Products::class)
            ->call('confirmDelete', $product->getKey())
            ->assertSet('confirmingDeleteProductId', $product->getKey())
            ->call('delete')
            ->assertSee(__('admin.products.messages.deleted'));

        $this->assertSoftDeleted('products', ['id' => $product->getKey()]);
    }

    #[Test]
    public function product_update_keeps_the_current_image_when_no_replacement_is_uploaded(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Storage::disk('public')->put('products/existing.png', 'existing image');
        $product = Product::factory()->create([
            'url_img' => '/storage/products/existing.png',
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        Livewire::actingAs($admin)
            ->test(EditProduct::class, ['product' => $product])
            ->set('name', 'Image Kept Product')
            ->set('quantity', 3)
            ->set('price', 2000)
            ->set('game_id', $game->getKey())
            ->set('rarity_id', $rarity->getKey())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'name' => 'Image Kept Product',
            'url_img' => '/storage/products/existing.png',
        ]);
        Storage::disk('public')->assertExists('products/existing.png');
    }

    #[Test]
    public function product_validation_errors_are_shown_in_livewire(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        $game->delete();
        $rarity->delete();

        Livewire::actingAs($admin)
            ->test(Products::class)
            ->call('beginCreate')
            ->set('name', 'Broken Product')
            ->set('image', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
            ->set('quantity', -1)
            ->set('price', -20)
            ->set('game_id', $game->getKey())
            ->set('rarity_id', $rarity->getKey())
            ->call('save')
            ->assertHasErrors(['image', 'quantity', 'price', 'game_id', 'rarity_id']);
    }

    #[Test]
    public function non_admin_users_cannot_reach_the_products_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_products_index_is_paginated_links_to_edit_and_truncates_long_names(): void
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        Product::factory()->count(11)->sequence(
            fn ($sequence) => [
                'name' => sprintf('Very Long Product Name %02d With Extra Operational Text', $sequence->index + 1),
                'game_id' => $game->getKey(),
                'rarity_id' => $rarity->getKey(),
            ],
        )->create();

        $firstProduct = Product::query()->where('name', 'Very Long Product Name 01 With Extra Operational Text')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('truncate font-medium text-white', false)
            ->assertSee(route('admin.products.edit', ['product' => $firstProduct]), false)
            ->assertDontSee('Very Long Product Name 11 With Extra Operational Text');
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
