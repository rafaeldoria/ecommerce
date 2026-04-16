<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QualityGuardrailsTest extends TestCase
{
    #[Test]
    public function localization_baseline_files_exist(): void
    {
        $this->assertFileExists(lang_path('en/general.php'));
        $this->assertFileExists(lang_path('pt_BR/general.php'));
    }
}
