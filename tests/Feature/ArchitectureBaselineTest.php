<?php

namespace Tests\Feature;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ArchitectureBaselineTest extends TestCase
{
    #[Test]
    public function modular_architecture_keeps_only_current_module_layers(): void
    {
        $paths = [
            app_path('Modules/Catalog/Actions/CreateProductAction.php'),
            app_path('Modules/Catalog/DTOs/CreateProductData.php'),
            app_path('Modules/Catalog/DomainServices/ProductWriteRules.php'),
            app_path('Modules/Catalog/Models/Product.php'),
            app_path('Modules/Catalog/ProductImages/ProductImageStorage.php'),
            app_path('Modules/Catalog/Queries/SearchCatalogProductsQuery.php'),
            app_path('Modules/Cart/Actions/AddToCartAction.php'),
            app_path('Modules/Cart/Contracts/CartStore.php'),
            app_path('Modules/Cart/DTOs/AddToCartData.php'),
            app_path('Modules/Cart/Queries/FindCartProductQuery.php'),
            app_path('Modules/Orders/Actions/CreateOrderAction.php'),
            app_path('Modules/Orders/DTOs/CreateOrderData.php'),
            app_path('Modules/Orders/Events/OrderCreated.php'),
            app_path('Modules/Orders/Models/Order.php'),
            app_path('Modules/Orders/Queries/ListAdminOrdersQuery.php'),
            app_path('Modules/Payments/Actions/CapturePaymentAction.php'),
            app_path('Modules/Payments/DTOs/CapturePaymentData.php'),
            app_path('Modules/Admin/Actions/AuthenticateAdminAction.php'),
            app_path('Modules/Admin/DTOs/AdminLoginData.php'),
        ];

        foreach ($paths as $path) {
            $this->assertFileExists($path);
        }

        $this->assertSame([], $this->moduleGitkeepFiles());
    }

    /**
     * @return list<string>
     */
    private function moduleGitkeepFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path('Modules'), FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getFilename() === '.gitkeep') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
