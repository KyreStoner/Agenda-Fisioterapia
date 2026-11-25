<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paletas de cores do sistema.
 *
 * As paletas ficam em application/config/color_palettes.php. A da clínica é escolhida em Configurações → Geral
 * (configuração "color_palette") e vale para todo mundo: painel, agendamento do paciente, login e e-mails. Cada
 * usuário pode escolher outra para o painel em Conta → Cores do sistema (user_settings.color_palette; vazio = segue a
 * clínica).
 *
 * Ids reservados:
 *  - "default": verde original do tema, sem trocar nenhuma cor;
 *  - "custom": uma cor só, escolhida em Configurações → Geral → Cor personalizada ("company_color").
 */

const COLOR_PALETTE_ORIGINAL = 'default';
const COLOR_PALETTE_CUSTOM = 'custom';

if (!function_exists('color_palette_options')) {
    /**
     * Paletas que podem ser escolhidas, na ordem de exibição: o verde original primeiro.
     *
     * @return array Paletas indexadas pelo id. Cada uma tem id, name, swatches, original (bool) e as cores.
     */
    function color_palette_options(): array
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $CI->config->load('color_palettes', true);

        $original = $CI->config->item('color_palette_original', 'color_palettes') ?? [];

        $options = [
            COLOR_PALETTE_ORIGINAL => [
                'id' => COLOR_PALETTE_ORIGINAL,
                'name' => $original['name'] ?? 'Verde (original)',
                'swatches' => $original['swatches'] ?? ['#429A82'],
                'primary' => palette_hex($original['primary'] ?? '#429A82'),
                'original' => true,
            ],
        ];

        foreach ($CI->config->item('color_palettes', 'color_palettes') ?? [] as $id => $palette) {
            if (in_array($id, [COLOR_PALETTE_ORIGINAL, COLOR_PALETTE_CUSTOM], true) || empty($palette['primary'])) {
                continue;
            }

            $options[$id] = palette_normalize(array_merge($palette, ['id' => (string) $id]));
        }

        return $options;
    }
}

if (!function_exists('palette_normalize')) {
    /**
     * Completa as cores que faltarem numa paleta e deixa todas no formato #rrggbb.
     *
     * @param array $palette Precisa ter pelo menos "primary".
     *
     * @return array
     */
    function palette_normalize(array $palette): array
    {
        $primary = palette_hex($palette['primary']);
        $dark = palette_hex($palette['dark'] ?? palette_mix($primary, '#000000', 0.75));
        $light = palette_hex($palette['light'] ?? palette_mix($primary, '#ffffff', 0.15));

        return [
            'id' => $palette['id'] ?? '',
            'name' => $palette['name'] ?? ($palette['id'] ?? ''),
            'swatches' => array_map('palette_hex', $palette['swatches'] ?? [$dark, $primary, $light]),
            'original' => false,
            'primary' => $primary,
            'dark' => $dark,
            'accent' => palette_hex($palette['accent'] ?? palette_mix($primary, '#ffffff', 0.55)),
            'light' => $light,
            'background' => palette_hex($palette['background'] ?? palette_mix($light, '#ffffff', 0.35)),
        ];
    }
}

if (!function_exists('custom_color_palette')) {
    /**
     * Paleta montada a partir de uma cor só (Configurações → Geral → Cor personalizada).
     *
     * Mantém o visual de antes das paletas: a barra do menu fica na própria cor.
     *
     * @param string $color Cor em hexadecimal.
     *
     * @return array
     */
    function custom_color_palette(string $color): array
    {
        $color = palette_hex($color);

        return palette_normalize([
            'id' => COLOR_PALETTE_CUSTOM,
            'name' => lang('color_palette_custom') ?: 'Personalizada',
            'primary' => $color,
            'dark' => $color,
            'swatches' => [palette_mix($color, '#000000', 0.75), $color, palette_mix($color, '#ffffff', 0.15)],
        ]);
    }
}

if (!function_exists('find_color_palette')) {
    /**
     * Paleta pelo id, ou null se não existir. Não resolve "custom" (use system_color_palette()).
     *
     * @param string|null $id
     *
     * @return array|null
     */
    function find_color_palette(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        return color_palette_options()[$id] ?? null;
    }
}

if (!function_exists('company_color_is_set')) {
    /**
     * Diz se a clínica tem uma cor personalizada válida (o "#ffffff" é o valor de fábrica, que significa "nenhuma").
     *
     * @return bool
     */
    function company_color_is_set(): bool
    {
        $company_color = (string) setting('company_color');

        return preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $company_color) === 1 &&
            strtolower($company_color) !== strtolower(DEFAULT_COMPANY_COLOR);
    }
}

if (!function_exists('system_color_palette_id')) {
    /**
     * Id da paleta da clínica (Configurações → Geral).
     *
     * Antes de existir a configuração "color_palette", vale a cor personalizada, se houver.
     *
     * @return string
     */
    function system_color_palette_id(): string
    {
        $id = (string) setting('color_palette', '');

        if ($id === '') {
            return company_color_is_set() ? COLOR_PALETTE_CUSTOM : COLOR_PALETTE_ORIGINAL;
        }

        if ($id === COLOR_PALETTE_CUSTOM) {
            return company_color_is_set() ? COLOR_PALETTE_CUSTOM : COLOR_PALETTE_ORIGINAL;
        }

        return find_color_palette($id) ? $id : COLOR_PALETTE_ORIGINAL;
    }
}

if (!function_exists('system_color_palette')) {
    /**
     * Paleta da clínica, já resolvida (inclusive a cor personalizada).
     *
     * @return array
     */
    function system_color_palette(): array
    {
        $id = system_color_palette_id();

        if ($id === COLOR_PALETTE_CUSTOM) {
            return custom_color_palette((string) setting('company_color'));
        }

        return find_color_palette($id) ?? color_palette_options()[COLOR_PALETTE_ORIGINAL];
    }
}

if (!function_exists('user_color_palette_id')) {
    /**
     * Paleta que o usuário escolheu para si em Conta → Cores do sistema ("" = segue a da clínica).
     *
     * @param int|null $user_id Padrão: o usuário logado.
     *
     * @return string
     */
    function user_color_palette_id(?int $user_id = null): string
    {
        $user_id = $user_id ?? (int) session('user_id');

        if (!$user_id) {
            return '';
        }

        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $settings = $CI->db->get_where('user_settings', ['id_users' => $user_id])->row_array();

        $id = (string) ($settings['color_palette'] ?? '');

        return find_color_palette($id) ? $id : '';
    }
}

if (!function_exists('active_color_palette')) {
    /**
     * Paleta de quem está vendo a página: a do usuário logado, se ele escolheu uma, senão a da clínica.
     *
     * Use nas páginas do painel. Páginas públicas (agendamento, login) e e-mails usam system_color_palette().
     *
     * @return array
     */
    function active_color_palette(): array
    {
        return find_color_palette(user_color_palette_id()) ?? system_color_palette();
    }
}

if (!function_exists('clinic_color')) {
    /**
     * Cor principal da clínica, para lugares sem CSS dinâmico (e-mails).
     *
     * @return string Cor em #rrggbb.
     */
    function clinic_color(): string
    {
        return system_color_palette()['primary'];
    }
}

if (!function_exists('palette_theme_color')) {
    /**
     * Cor da barra do navegador no celular (meta theme-color).
     *
     * @param array $palette
     *
     * @return string
     */
    function palette_theme_color(array $palette): string
    {
        return $palette['dark'] ?? $palette['primary'];
    }
}

if (!function_exists('color_palette_css')) {
    /**
     * CSS que aplica a paleta sobre o tema (Bootstrap + estilos do sistema).
     *
     * O verde original não gera nada: fica o tema como ele é.
     *
     * @param array $palette Paleta de color_palette_options(), system_color_palette() ou custom_color_palette().
     *
     * @return string CSS sem a tag <style>.
     */
    function color_palette_css(array $palette): string
    {
        if (!empty($palette['original'])) {
            return '';
        }

        $p = palette_normalize($palette);

        $primary = $p['primary'];
        $dark = $p['dark'];
        $accent = $p['accent'];
        $light = $p['light'];
        $background = $p['background'];

        $primary_text = palette_text_color($primary);
        $dark_text = palette_text_color($dark);
        $accent_text = palette_text_color($accent);
        $light_text = palette_contrast($dark, $light) >= 4.5 ? $dark : palette_text_color($light);

        $hover = palette_mix($primary, '#ffffff', 0.85);
        $active = palette_mix($primary, '#000000', 0.85);
        $emphasis = palette_mix($dark, '#000000', 0.8);
        $subtle = palette_mix($light, '#ffffff', 0.7);
        $border_subtle = palette_mix($primary, '#ffffff', 0.4);
        $focus_border = palette_mix($primary, '#ffffff', 0.6);
        $darker = palette_mix($dark, '#000000', 0.82);

        // Link legível sobre fundo branco, mesmo com uma cor personalizada clara.
        $link = palette_contrast($primary, '#ffffff') >= 4.5 ? $primary : $emphasis;
        $link_hover = palette_mix($link, '#000000', 0.8);

        $rgb = 'palette_rgb_string';

        // Temas escuros (Bootswatch "darkly") ficam com o fundo deles.
        $dark_theme = in_array(setting('theme', 'default'), ['darkly'], true);

        $css = <<<CSS
        :root,
        [data-bs-theme="light"],
        [data-bs-theme="dark"] {
            --bs-primary: {$primary};
            --bs-primary-rgb: {$rgb($primary)};
            --bs-primary-bg-subtle: {$subtle};
            --bs-primary-border-subtle: {$border_subtle};
            --bs-primary-text-emphasis: {$emphasis};
            --bs-link-color: {$link};
            --bs-link-color-rgb: {$rgb($link)};
            --bs-link-hover-color: {$link_hover};
            --bs-link-hover-color-rgb: {$rgb($link_hover)};
            --bs-focus-ring-color: rgba({$rgb($primary)}, 0.25);
            --ea-primary-hover: {$hover};
            --ea-primary-active: {$active};
            --ea-primary-dark: {$dark};
            --ea-primary-darker: {$emphasis};
            --ea-primary-subtle: {$subtle};
            --ea-palette-primary: {$primary};
            --ea-palette-primary-text: {$primary_text};
            --ea-palette-dark: {$dark};
            --ea-palette-dark-rgb: {$rgb($dark)};
            --ea-palette-dark-text: {$dark_text};
            --ea-palette-accent: {$accent};
            --ea-palette-accent-rgb: {$rgb($accent)};
            --ea-palette-accent-text: {$accent_text};
            --ea-palette-light: {$light};
            --ea-palette-light-rgb: {$rgb($light)};
            --ea-palette-light-text: {$light_text};
            --ea-palette-background: {$background};
        }

        /* Agenda (FullCalendar): as variáveis ficam no .fc porque o FullCalendar injeta as dele no :root depois deste CSS */
        .fc {
            --fc-today-bg-color: rgba({$rgb($light)}, 0.45);
            --fc-button-text-color: {$primary_text};
            --fc-button-bg-color: {$primary};
            --fc-button-border-color: {$primary};
            --fc-button-hover-bg-color: {$active};
            --fc-button-hover-border-color: {$active};
            --fc-button-active-bg-color: {$dark};
            --fc-button-active-border-color: {$dark};
        }

        /* Botões */
        .btn-primary {
            --bs-btn-color: {$primary_text};
            --bs-btn-bg: {$primary};
            --bs-btn-border-color: {$primary};
            --bs-btn-hover-color: {$primary_text};
            --bs-btn-hover-bg: {$hover};
            --bs-btn-hover-border-color: {$hover};
            --bs-btn-active-color: {$primary_text};
            --bs-btn-active-bg: {$active};
            --bs-btn-active-border-color: {$active};
            --bs-btn-disabled-color: {$primary_text};
            --bs-btn-disabled-bg: {$primary};
            --bs-btn-disabled-border-color: {$primary};
            --bs-btn-focus-shadow-rgb: {$rgb($primary)};
        }

        .btn-outline-primary {
            --bs-btn-color: {$link};
            --bs-btn-border-color: {$primary};
            --bs-btn-hover-color: {$primary_text};
            --bs-btn-hover-bg: {$primary};
            --bs-btn-hover-border-color: {$primary};
            --bs-btn-active-color: {$primary_text};
            --bs-btn-active-bg: {$active};
            --bs-btn-active-border-color: {$active};
            --bs-btn-disabled-color: {$primary};
            --bs-btn-disabled-border-color: {$primary};
            --bs-btn-focus-shadow-rgb: {$rgb($primary)};
        }

        .btn-primary,
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            color: {$primary_text};
        }

        /* Campos de formulário (o Bootstrap grava a cor do tema direto nestas regras) */
        .form-control:focus,
        .form-select:focus {
            border-color: {$focus_border};
            box-shadow: 0 0 0 3px rgba({$rgb($primary)}, 0.2);
        }

        .form-check-input:checked,
        .form-check-input[type="checkbox"]:indeterminate {
            background-color: {$primary};
            border-color: {$primary};
        }

        .form-check-input:focus {
            border-color: {$focus_border};
            box-shadow: 0 0 0 3px rgba({$rgb($primary)}, 0.2);
        }

        .form-range::-webkit-slider-thumb {
            background-color: {$primary};
        }

        .form-range::-moz-range-thumb {
            background-color: {$primary};
        }

        /* Navegação, listas e outros componentes */
        .nav-pills {
            --bs-nav-pills-link-active-bg: {$primary};
            --bs-nav-pills-link-active-color: {$primary_text};
        }

        .nav-link {
            --bs-nav-link-color: {$link};
            --bs-nav-link-hover-color: {$link_hover};
        }

        .nav-tabs .nav-link.active {
            color: {$link};
        }

        .dropdown-menu {
            --bs-dropdown-link-active-bg: {$primary};
            --bs-dropdown-link-active-color: {$primary_text};
        }

        .pagination {
            --bs-pagination-color: {$link};
            --bs-pagination-active-bg: {$primary};
            --bs-pagination-active-border-color: {$primary};
            --bs-pagination-active-color: {$primary_text};
        }

        .progress,
        .progress-stacked {
            --bs-progress-bar-bg: {$primary};
        }

        .list-group {
            --bs-list-group-active-bg: {$primary};
            --bs-list-group-active-border-color: {$primary};
            --bs-list-group-active-color: {$primary_text};
        }

        .accordion {
            --bs-accordion-active-color: {$emphasis};
            --bs-accordion-active-bg: {$subtle};
            --bs-accordion-btn-focus-box-shadow: 0 0 0 3px rgba({$rgb($primary)}, 0.2);
        }

        .bg-primary,
        .text-bg-primary {
            color: {$primary_text};
        }

        .badge.bg-primary,
        .text-bg-primary {
            color: {$primary_text} !important;
        }

        ::selection {
            background: rgba({$rgb($accent)}, 0.35);
        }

        /* Cabeçalho do painel: cor escura da paleta, com o item aberto marcado na cor de destaque */
        #header.navbar,
        nav#header {
            background-color: {$dark} !important;
            background-image: none !important;
            border-color: {$dark} !important;
        }

        #header #header-menu .nav-item.active {
            background: rgba(0, 0, 0, 0.2);
            box-shadow: inset 0 -3px 0 {$accent};
        }

        /* Faixa de filtros da agenda, logo abaixo do menu */
        #calendar-toolbar.bg-dark {
            background-color: {$darker} !important;
        }

        /* Janelas (modais) */
        body .modal-header {
            background: {$dark};
        }

        body .modal-header .modal-title,
        body .modal-header h3 {
            color: {$dark_text};
        }

        /* Listas de registros do painel (pacientes, profissionais, serviços...) */
        .backend-page .filter-records .results .entry.selected {
            border-right-color: {$accent} !important;
            background-color: rgba({$rgb($light)}, 0.45);
        }

        #existing-customers-list div:hover {
            background: {$primary} !important;
            color: {$primary_text} !important;
        }

        /* Página de agendamento do paciente */
        #book-appointment-wizard #header {
            background-color: {$dark} !important;
        }

        #book-appointment-wizard #header #company-name,
        #book-appointment-wizard #header #company-name > span {
            color: {$dark_text} !important;
        }

        #book-appointment-wizard .book-step.active-step {
            background: {$light} !important;
        }

        #book-appointment-wizard .book-step.active-step strong {
            color: {$light_text} !important;
        }

        #book-appointment-wizard #available-hours .available-hour:hover {
            border-color: {$accent};
        }

        #book-appointment-wizard #available-hours .selected-hour {
            background-color: {$primary} !important;
            border-color: {$primary} !important;
            color: {$primary_text} !important;
        }

        #book-appointment-wizard #company-name .display-selected-service,
        #book-appointment-wizard #company-name .display-selected-provider,
        #book-appointment-wizard #company-name .display-booking-selection {
            border-right-color: rgba(255, 255, 255, 0.5);
        }

        #frame-footer .backend-link,
        #message-frame .backend-link {
            background-color: {$primary} !important;
            border-color: {$primary} !important;
            color: {$primary_text} !important;
        }

        /* Calendário de datas (flatpickr, tema "material_green"): cabeçalho na cor escura, dia escolhido na principal */
        .flatpickr-calendar .flatpickr-months,
        .flatpickr-months .flatpickr-month,
        .flatpickr-weekdays,
        span.flatpickr-weekday,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
            background: {$dark} !important;
            color: {$dark_text} !important;
        }

        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            color: {$dark_text} !important;
            fill: {$dark_text} !important;
        }

        .flatpickr-months .flatpickr-prev-month svg,
        .flatpickr-months .flatpickr-next-month svg {
            fill: {$dark_text} !important;
        }

        .flatpickr-calendar.arrowTop::after {
            border-bottom-color: {$dark} !important;
        }

        .flatpickr-calendar.arrowBottom::after {
            border-top-color: {$dark} !important;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.selected:hover,
        .flatpickr-day.selected:focus,
        .flatpickr-day.startRange:hover,
        .flatpickr-day.endRange:hover {
            background: {$primary} !important;
            border-color: {$primary} !important;
            color: {$primary_text} !important;
        }

        .flatpickr-day.today {
            border-color: {$accent} !important;
        }

        .flatpickr-day.today:hover,
        .flatpickr-day.today:focus {
            background: {$accent} !important;
            border-color: {$accent} !important;
            color: {$accent_text} !important;
        }

        .flatpickr-day.inRange {
            background: {$subtle} !important;
            border-color: {$subtle} !important;
            box-shadow: -5px 0 0 {$subtle}, 5px 0 0 {$subtle} !important;
        }

        CSS;

        if (!$dark_theme) {
            $css .= <<<CSS

            /* Fundo das páginas */
            body {
                background-color: {$background} !important;
                background-image: none !important;
            }

            CSS;
        }

        return $css;
    }
}

if (!function_exists('palette_hex')) {
    /**
     * Cor no formato #rrggbb (minúsculas). Valores inválidos viram o verde original.
     *
     * Também impede que um valor gravado no banco quebre o bloco <style>.
     *
     * @param string $hex
     *
     * @return string
     */
    function palette_hex(string $hex): string
    {
        $hex = strtolower(preg_replace('/[^0-9A-Fa-f]/', '', $hex));

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return '#429a82';
        }

        return '#' . $hex;
    }
}

if (!function_exists('palette_rgb')) {
    /**
     * @param string $hex
     *
     * @return int[] [r, g, b]
     */
    function palette_rgb(string $hex): array
    {
        $hex = ltrim(palette_hex($hex), '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}

if (!function_exists('palette_rgb_string')) {
    /**
     * @param string $hex
     *
     * @return string "r, g, b", para usar em rgba().
     */
    function palette_rgb_string(string $hex): string
    {
        return implode(', ', palette_rgb($hex));
    }
}

if (!function_exists('palette_mix')) {
    /**
     * Mistura duas cores.
     *
     * @param string $color Cor principal.
     * @param string $with Cor misturada.
     * @param float $weight Quanto da cor principal fica (0 a 1).
     *
     * @return string
     */
    function palette_mix(string $color, string $with, float $weight): string
    {
        $a = palette_rgb($color);
        $b = palette_rgb($with);

        $mixed = [];

        for ($i = 0; $i < 3; $i++) {
            $mixed[] = (int) round($a[$i] * $weight + $b[$i] * (1 - $weight));
        }

        return vsprintf('#%02x%02x%02x', $mixed);
    }
}

if (!function_exists('palette_luminance')) {
    /**
     * Luminância relativa (WCAG).
     *
     * @param string $hex
     *
     * @return float
     */
    function palette_luminance(string $hex): float
    {
        $channels = array_map(function (int $value) {
            $value /= 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, palette_rgb($hex));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}

if (!function_exists('palette_contrast')) {
    /**
     * Contraste entre duas cores (WCAG): 4.5 é o mínimo para texto normal.
     *
     * @param string $a
     * @param string $b
     *
     * @return float
     */
    function palette_contrast(string $a, string $b): float
    {
        $la = palette_luminance($a);
        $lb = palette_luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }
}

if (!function_exists('palette_text_color')) {
    /**
     * Branco ou quase preto, o que for mais legível sobre a cor de fundo.
     *
     * @param string $background
     *
     * @return string
     */
    function palette_text_color(string $background): string
    {
        return palette_contrast($background, '#ffffff') >= palette_contrast($background, '#1d1d1f')
            ? '#ffffff'
            : '#1d1d1f';
    }
}
