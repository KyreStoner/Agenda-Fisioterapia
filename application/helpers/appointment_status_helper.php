<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Situações dos atendimentos e as cores delas na agenda.
 *
 * A lista é a de Configurações → Agendamento ("appointment_status_options": nomes, na ordem; a primeira é a de toda
 * consulta nova) e as cores ficam em "appointment_status_colors" (nome → cor). Situação sem cor salva usa a cor
 * padrão de application/config/appointment_statuses.php com o mesmo nome, ou a cor padrão de uma situação nova.
 */

if (!function_exists('appointment_status_defaults')) {
    /**
     * Lista padrão (application/config/appointment_statuses.php).
     *
     * @return array [['name' => ..., 'color' => ..., 'hint' => ...], ...]
     */
    function appointment_status_defaults(): array
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $CI->config->load('appointment_statuses', true);

        return $CI->config->item('appointment_statuses', 'appointment_statuses') ?? [];
    }
}

if (!function_exists('appointment_status_options')) {
    /**
     * Nomes das situações, na ordem configurada.
     *
     * @return string[]
     */
    function appointment_status_options(): array
    {
        $options = json_decode((string) setting('appointment_status_options', '[]'), true);

        if (!is_array($options)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $options), fn($name) => trim($name) !== ''));
    }
}

if (!function_exists('appointment_status_items')) {
    /**
     * Situações com a cor de cada uma, na ordem configurada.
     *
     * @return array [['name' => ..., 'color' => '#rrggbb', 'hint' => ...], ...]
     */
    function appointment_status_items(): array
    {
        $saved_colors = json_decode((string) setting('appointment_status_colors', '{}'), true);

        $saved_colors = is_array($saved_colors) ? $saved_colors : [];

        $defaults = [];

        foreach (appointment_status_defaults() as $default) {
            $defaults[mb_strtolower($default['name'])] = $default;
        }

        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $fallback_color = $CI->config->item('appointment_status_default_color', 'appointment_statuses') ?: '#7cbae8';

        $items = [];

        foreach (appointment_status_options() as $name) {
            $default = $defaults[mb_strtolower($name)] ?? null;

            $color = $saved_colors[$name] ?? ($default['color'] ?? $fallback_color);

            $items[] = [
                'name' => $name,
                'color' => palette_hex((string) $color),
                'hint' => $default['hint'] ?? '',
            ];
        }

        return $items;
    }
}

if (!function_exists('appointment_status_colors')) {
    /**
     * Cor de cada situação (nome → #rrggbb), para a agenda.
     *
     * @return array
     */
    function appointment_status_colors(): array
    {
        $colors = [];

        foreach (appointment_status_items() as $item) {
            $colors[$item['name']] = $item['color'];
        }

        return $colors;
    }
}

if (!function_exists('appointment_status_automation')) {
    /**
     * Nome da situação de uma mudança automática ("overbooked" ou "completed"), se ela existir na lista atual.
     *
     * @param string $key
     *
     * @return string|null
     */
    function appointment_status_automation(string $key): ?string
    {
        appointment_status_defaults(); // Carrega application/config/appointment_statuses.php.

        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $automation = $CI->config->item('appointment_status_automation', 'appointment_statuses') ?? [];

        $name = $automation[$key] ?? null;

        return is_string($name) && in_array($name, appointment_status_options(), true) ? $name : null;
    }
}

if (!function_exists('appointment_status_keeps_on_completion')) {
    /**
     * Diz se a situação fica como está quando o atendimento é registrado no prontuário (Faltou, Cancelado...).
     *
     * @param string|null $status
     *
     * @return bool
     */
    function appointment_status_keeps_on_completion(?string $status): bool
    {
        appointment_status_defaults();

        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $automation = $CI->config->item('appointment_status_automation', 'appointment_statuses') ?? [];

        return in_array((string) $status, $automation['completed_keep'] ?? [], true);
    }
}
