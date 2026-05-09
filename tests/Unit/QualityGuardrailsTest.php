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

    #[Test]
    public function production_facing_storefront_footer_copy_does_not_use_placeholder_wording(): void
    {
        $englishCopy = require lang_path('en/storefront.php');
        $portugueseCopy = require lang_path('pt_BR/storefront.php');
        $blockedWord = 'de'.'mo';

        $this->assertStringNotContainsStringIgnoringCase($blockedWord, $englishCopy['footer']['copyright']);
        $this->assertStringNotContainsStringIgnoringCase($blockedWord, $portugueseCopy['footer']['copyright']);
    }
}
