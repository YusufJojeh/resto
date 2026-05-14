<?php

namespace Tests\Unit\Support;

use App\Modules\Public\Support\BrandTokens;
use PHPUnit\Framework\TestCase;

class BrandTokensTest extends TestCase
{
    public function test_css_vars_returns_correct_string(): void
    {
        $tokens = [
            'primary_color'   => '#1a1a2e',
            'secondary_color' => '#16213e',
            'accent_color'    => '#e94560',
        ];

        $css = BrandTokens::cssVars($tokens);

        $this->assertSame(
            '--brand-primary: #1a1a2e;--brand-secondary: #16213e;--brand-accent: #e94560',
            $css,
        );
    }

    public function test_css_vars_uses_provided_colors_verbatim(): void
    {
        $tokens = [
            'primary_color'   => '#ffffff',
            'secondary_color' => '#000000',
            'accent_color'    => '#abcdef',
        ];

        $css = BrandTokens::cssVars($tokens);

        $this->assertStringContainsString('--brand-primary: #ffffff', $css);
        $this->assertStringContainsString('--brand-secondary: #000000', $css);
        $this->assertStringContainsString('--brand-accent: #abcdef', $css);
    }
}
