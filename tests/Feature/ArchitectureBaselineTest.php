<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchitectureBaselineTest extends TestCase
{
    #[Test]
    public function modular_architecture_directories_exist(): void
    {
        $paths = [
            app_path('Modules/Catalog/Actions'),
            app_path('Modules/Catalog/DTOs'),
            app_path('Modules/Catalog/Models'),
            app_path('Modules/Catalog/Policies'),
            app_path('Modules/Catalog/Queries'),
            app_path('Modules/Cart/Actions'),
            app_path('Modules/Cart/DTOs'),
            app_path('Modules/Cart/Models'),
            app_path('Modules/Orders/Actions'),
            app_path('Modules/Orders/DTOs'),
            app_path('Modules/Orders/Events'),
            app_path('Modules/Orders/Models'),
            app_path('Modules/Orders/Queries'),
            app_path('Modules/Payments/Actions'),
            app_path('Modules/Payments/DTOs'),
            app_path('Modules/Payments/Gateways'),
            app_path('Modules/Admin/Actions'),
            app_path('Modules/Admin/Policies'),
            app_path('Modules/Admin/Queries'),
        ];

        foreach ($paths as $path) {
            $this->assertDirectoryExists($path);
        }
    }
}
