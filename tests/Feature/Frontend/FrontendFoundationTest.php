<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Admin\Login;
use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class FrontendFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function storefront_routes_render_with_the_public_layout(): void
    {
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $product = Product::factory()->create([
            'name' => 'Phantom Assassin Arcana',
            'game_id' => $game->id,
            'rarity_id' => $rarity->id,
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee('<title>GR-Shop</title>', false)
            ->assertSee(__('storefront.brand.name'))
            ->assertSee(__('storefront.home.title'))
            ->assertSee(__('storefront.navigation.cart'))
            ->assertSee(__('storefront.footer.about_title'));

        $this->get(route('storefront.catalog'))
            ->assertOk()
            ->assertSee('<title>Catalog | GR-Shop</title>', false)
            ->assertSee(__('storefront.catalog.title'))
            ->assertSee('Dota 2')
            ->assertSee('Phantom Assassin Arcana');

        $this->get(route('storefront.products.show', ['product' => $product]))
            ->assertOk()
            ->assertSee('<title>Product | GR-Shop</title>', false)
            ->assertSee('Phantom Assassin Arcana')
            ->assertSee('Dota 2')
            ->assertSee('Arcana');
    }

    #[Test]
    public function catalog_filters_products_by_game_slug(): void
    {
        $dota = Game::factory()->create(['name' => 'Dota 2']);
        $cs2 = Game::factory()->create(['name' => 'CS2']);
        $rarity = Rarity::factory()->create(['name' => 'Immortal']);

        Product::factory()->create([
            'name' => 'Invoker Relic Set',
            'game_id' => $dota->id,
            'rarity_id' => $rarity->id,
        ]);

        Product::factory()->create([
            'name' => 'AK-47 Neon Rider',
            'game_id' => $cs2->id,
            'rarity_id' => $rarity->id,
        ]);

        $this->get(route('storefront.catalog', ['game' => 'dota-2']))
            ->assertOk()
            ->assertSee('Invoker Relic Set')
            ->assertDontSee('AK-47 Neon Rider');
    }

    #[Test]
    public function support_storefront_routes_are_available(): void
    {
        $this->get(route('storefront.about'))
            ->assertOk()
            ->assertSee(__('storefront.content.about_title'));

        $this->get(route('storefront.contact'))
            ->assertOk()
            ->assertSee(__('storefront.content.contact_title'));

        $this->get(route('storefront.faq'))
            ->assertOk()
            ->assertSee(__('storefront.content.faq_title'));
    }

    #[Test]
    public function admin_routes_are_separate_and_protected_after_login(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('<title>Admin login</title>', false)
            ->assertSee(__('admin.auth.login_title'))
            ->assertDontSee(__('storefront.navigation.catalog'));

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<title>Admin dashboard</title>', false)
            ->assertSee(__('admin.dashboard.title'))
            ->assertSee(__('admin.navigation.products'));
    }

    #[Test]
    public function sold_out_products_are_not_publicly_accessible_in_the_storefront(): void
    {
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $soldOutProduct = Product::factory()->create([
            'name' => 'Sold Out Arcana',
            'game_id' => $game->id,
            'rarity_id' => $rarity->id,
            'quantity' => 0,
        ]);

        $this->get(route('storefront.products.show', ['product' => $soldOutProduct]))
            ->assertNotFound();
    }

    #[Test]
    public function admin_can_log_in_from_the_web_panel(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        Livewire::test(Login::class)
            ->set('login', 'ops-admin')
            ->set('password', 'secret-pass')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function authenticated_admin_is_redirected_away_from_login_screen(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function admin_web_login_rejects_non_admin_users(): void
    {
        User::factory()->customer()->create([
            'username' => 'shopper',
            'password' => 'secret-pass',
        ]);

        Livewire::test(Login::class)
            ->set('login', 'shopper')
            ->set('password', 'secret-pass')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        $this->assertGuest();
    }

    #[Test]
    public function frontend_copy_can_render_in_pt_br(): void
    {
        app()->setLocale('pt_BR');

        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        Product::factory()->create([
            'name' => 'Machado Arcano',
            'game_id' => $game->id,
            'rarity_id' => $rarity->id,
        ]);

        $this->get(route('storefront.catalog', ['game' => 'dota-2']))
            ->assertOk()
            ->assertSee('Catalogo')
            ->assertSee('Troque de game instantaneamente')
            ->assertSee('Machado Arcano');
    }

    #[Test]
    public function admin_login_throttle_key_includes_the_client_ip(): void
    {
        $component = app(Login::class);
        $method = new ReflectionMethod(Login::class, 'rateLimitKey');
        $method->setAccessible(true);

        app()->instance('request', Request::create('/admin/login', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1']));
        $firstKey = $method->invoke($component, 'ops-admin');

        app()->instance('request', Request::create('/admin/login', 'POST', server: ['REMOTE_ADDR' => '10.0.0.2']));
        $secondKey = $method->invoke($component, 'ops-admin');

        $this->assertSame('ops-admin|10.0.0.1', $firstKey);
        $this->assertSame('ops-admin|10.0.0.2', $secondKey);
        $this->assertNotSame($firstKey, $secondKey);
    }
}
