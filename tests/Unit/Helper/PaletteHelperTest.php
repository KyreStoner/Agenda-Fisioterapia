<?php

namespace Tests\Unit\Helper;

use Tests\TestCase;

/**
 * Paletas de cores (application/helpers/palette_helper.php e application/config/color_palettes.php).
 */
class PaletteHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['ea_test_settings'] = [];
    }

    public function testPaletteHexNormalizesShortAndUppercaseValues(): void
    {
        $this->assertSame('#aabbcc', palette_hex('#ABC'));
        $this->assertSame('#2f4a63', palette_hex('#2F4A63'));
    }

    public function testPaletteHexNeverLetsMarkupThrough(): void
    {
        // Um valor estragado no banco não pode fechar o bloco <style>: vira o verde original.
        $this->assertSame('#429a82', palette_hex('red;}</style><script>'));
    }

    public function testPaletteMixBlendsTwoColors(): void
    {
        $this->assertSame('#808080', palette_mix('#000000', '#ffffff', 0.5));
        $this->assertSame('#aa1c41', palette_mix('#AA1C41', '#ffffff', 1));
    }

    public function testPaletteContrastFollowsWcag(): void
    {
        $this->assertEqualsWithDelta(21.0, palette_contrast('#000000', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(1.0, palette_contrast('#5b91a3', '#5b91a3'), 0.01);
    }

    public function testPaletteTextColorPicksTheReadableOne(): void
    {
        $this->assertSame('#ffffff', palette_text_color('#AA1C41'));
        $this->assertSame('#1d1d1f', palette_text_color('#FFE8B4'));
        $this->assertSame('#1d1d1f', palette_text_color('#E68457'));
    }

    public function testOptionsStartWithTheOriginalGreen(): void
    {
        $options = color_palette_options();

        $this->assertSame(COLOR_PALETTE_ORIGINAL, array_key_first($options));
        $this->assertTrue($options[COLOR_PALETTE_ORIGINAL]['original']);

        foreach (['marinho', 'oliva', 'sereno', 'vinho'] as $id) {
            $this->assertArrayHasKey($id, $options);
            $this->assertCount(4, $options[$id]['swatches']);
        }

        $this->assertArrayNotHasKey(COLOR_PALETTE_CUSTOM, $options);
    }

    public function testConfiguredPalettesKeepWhiteTextReadable(): void
    {
        // Regra do arquivo de configuração: botões (primary) e menu (dark) levam texto branco.
        foreach (color_palette_options() as $id => $palette) {
            if ($palette['original']) {
                continue;
            }

            $this->assertGreaterThanOrEqual(4.5, palette_contrast($palette['primary'], '#ffffff'), $id . ' primary');
            $this->assertGreaterThanOrEqual(4.5, palette_contrast($palette['dark'], '#ffffff'), $id . ' dark');
        }
    }

    public function testSystemPaletteUsesTheSavedPalette(): void
    {
        setting(['color_palette' => 'vinho']);

        $this->assertSame('vinho', system_color_palette_id());
        $this->assertSame('#aa1c41', clinic_color());
    }

    public function testSystemPaletteFallsBackToTheOriginalGreen(): void
    {
        setting(['color_palette' => 'paleta-apagada']);
        $this->assertSame(COLOR_PALETTE_ORIGINAL, system_color_palette_id());

        // "Personalizada" sem cor escolhida ("#ffffff" é o valor de fábrica).
        setting(['color_palette' => COLOR_PALETTE_CUSTOM, 'company_color' => '#ffffff']);
        $this->assertSame(COLOR_PALETTE_ORIGINAL, system_color_palette_id());
        $this->assertSame('#429a82', clinic_color());
    }

    public function testCustomColorStillWorksBeforeThePaletteSettingExists(): void
    {
        // Instalações antigas: sem "color_palette", vale a cor personalizada que já existia.
        setting(['company_color' => '#123456']);

        $this->assertSame(COLOR_PALETTE_CUSTOM, system_color_palette_id());
        $this->assertSame('#123456', clinic_color());
    }

    public function testOriginalGreenGeneratesNoCss(): void
    {
        $this->assertSame('', color_palette_css(color_palette_options()[COLOR_PALETTE_ORIGINAL]));
    }

    public function testPaletteCssAppliesEachRole(): void
    {
        $css = color_palette_css(find_color_palette('marinho'));

        $this->assertStringContainsString('--bs-primary: #2f4a63;', $css);
        $this->assertStringContainsString('--ea-palette-dark: #131b2b;', $css);
        $this->assertStringContainsString('--ea-palette-accent: #5b91a3;', $css);
        $this->assertStringContainsString('--ea-palette-light: #cfeae0;', $css);
        $this->assertStringNotContainsString('</style>', $css);
    }

    public function testFindColorPaletteRejectsReservedAndUnknownIds(): void
    {
        $this->assertNull(find_color_palette(COLOR_PALETTE_CUSTOM));
        $this->assertNull(find_color_palette('nao-existe'));
        $this->assertNull(find_color_palette(''));
        $this->assertNotNull(find_color_palette('sereno'));
    }
}
