<?php

namespace Tests\Feature\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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
        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee(__('storefront.brand.name'))
            ->assertSee(__('storefront.home.title'))
            ->assertSee(__('storefront.navigation.cart'));

        $this->get(route('storefront.catalog'))
            ->assertOk()
            ->assertSee(__('storefront.catalog.title'));

        $this->get(route('storefront.products.show', ['product' => '42']))
            ->assertOk()
            ->assertSee(__('storefront.product.title'))
            ->assertSee('42');
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
            ->assertSee(__('admin.auth.login_title'))
            ->assertDontSee(__('storefront.navigation.catalog'));

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard.title'))
            ->assertSee(__('admin.navigation.products'));
    }

    #[Test]
    public function frontend_copy_can_render_in_pt_br(): void
    {
        app()->setLocale('pt_BR');

        $this->get(route('storefront.cart'))
            ->assertOk()
            ->assertSee('Carrinho')
            ->assertSee('pt-BR');
    }
}
