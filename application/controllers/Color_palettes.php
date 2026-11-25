<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paletas de cores: prévia nas telas de escolha (Configurações → Geral e Conta).
 *
 * Devolve o CSS de uma paleta para a página trocar as cores na hora, antes de salvar. As paletas ficam em
 * application/config/color_palettes.php e as regras em application/helpers/palette_helper.php.
 */
class Color_palettes extends EA_Controller
{
    /**
     * CSS de uma paleta.
     *
     * Parâmetros (GET):
     *  - palette: id da paleta, "custom" (cor personalizada) ou "" (paleta da clínica);
     *  - color: cor da paleta "custom", em #rrggbb.
     */
    public function preview(): void
    {
        try {
            method('get');

            if (!session('user_id')) {
                abort(401, 'Unauthorized');

                return;
            }

            $id = (string) request('palette', '');

            if ($id === '') {
                $palette = system_color_palette();
            } elseif ($id === COLOR_PALETTE_CUSTOM) {
                $color = (string) request('color', '');

                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    throw new InvalidArgumentException(lang('color_palette_invalid'));
                }

                $palette = custom_color_palette($color);
            } else {
                $palette = find_color_palette($id);

                if (!$palette) {
                    throw new InvalidArgumentException(lang('color_palette_invalid'));
                }
            }

            json_response([
                'css' => color_palette_css($palette),
                'theme_color' => palette_theme_color($palette),
                'swatches' => $palette['swatches'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
