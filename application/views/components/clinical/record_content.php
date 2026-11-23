<?php
/**
 * Conteúdo de um registro do prontuário (avaliação ou evolução), agrupado por seção. Usado na tela do prontuário e no
 * relatório A4.
 *
 * Variáveis:
 *  - array $rc_form        Seções e campos da ficha.
 *  - array $rc_content     Valores salvos.
 *  - array $rc_catalog     Catálogo clínico (regiões, testes, questionários).
 *  - array $rc_goal_labels Texto das metas por id (para "metas atingidas").
 */
$catalog = $rc_catalog;
$goal_labels = $rc_goal_labels;


/**
 * Valor de um campo para exibição (HTML já escapado), ou '' se estiver vazio.
 */
$render_value = function (array $field, mixed $value, string $name = '') use ($catalog, $goal_labels): string {
    if ($value === '' || $value === [] || $value === null) {
        return '';
    }

    switch ($field['type']) {
        case 'body_map':
            $labels = array_map(fn($id) => e($catalog['regions'][$id]['label'] ?? $id), (array) $value);

            return component('clinical/body_map', [
                'bm_name' => '',
                'bm_selected' => (array) $value,
                'bm_regions' => $catalog['regions'],
                'bm_small' => true,
            ], true) . '<div class="mt-1">' . implode(', ', $labels) . '</div>';

        case 'region_tests':
            $html = '';

            foreach ((array) $value as $key => $block) {
                $area_id = isset($catalog['areas'][$key]) ? $key : preg_replace('/_[de]$/', '', $key);
                $area = $catalog['areas'][$area_id] ?? null;

                if (!$area) {
                    continue;
                }

                $side = str_ends_with($key, '_d') ? ' ' . lang('cr_side_right') : (str_ends_with($key, '_e') ? ' ' . lang('cr_side_left') : '');
                $items = [];

                foreach ($block['tests'] ?? [] as $test_id => $result) {
                    $badge = $result === 'positive' ? 'text-bg-danger' : ($result === 'negative' ? 'text-bg-success' : 'text-bg-light border');
                    $items[] = e($area['tests'][$test_id] ?? $test_id) . ' <span class="badge ' . $badge . '">' . e($catalog['test_results'][$result] ?? $result) . '</span>';
                }

                $questionnaire_labels = Clinical_records_model::area_questionnaires($area_id, $catalog);

                foreach ($block['questionnaires'] ?? [] as $questionnaire_id => $score) {
                    $answered = isset($block['answers'][$questionnaire_id]) ? ' <span class="text-muted small">(' . lang('cr_questionnaire_answered') . ')</span>' : '';
                    $items[] = e($questionnaire_labels[$questionnaire_id] ?? $questionnaire_id) . ': <strong>' . e($score) . '</strong>' . $answered;
                }

                $html .= '<div class="mb-1"><span class="fw-semibold">' . e($area['label'] . $side) . '</span><br>' . implode(' · ', $items) . '</div>';
            }

            return $html;

        case 'goals':
            $html = '<ul class="mb-0 ps-3">';

            foreach ((array) $value as $goal) {
                $html .= '<li>' . ($goal['week'] ? '<span class="text-muted">' . lang('cr_goal_week') . ' ' . (int) $goal['week'] . ':</span> ' : '')
                    . e($goal['goal']) . ($goal['plan'] !== '' ? '<div class="small text-muted" style="white-space: pre-line;">' . e($goal['plan']) . '</div>' : '') . '</li>';
            }

            return $html . '</ul>';

        case 'choice':
            return e(lang('cr_opt_' . $name . '_' . $value) ?: $value);

        case 'measure':
            return e(format_decimal($value) . ' ' . $field['unit']);

        case 'blood_pressure':
            return e(((int) ($value['sys'] ?? 0)) . '/' . ((int) ($value['dia'] ?? 0)) . ' mmHg');

        case 'bmi':
            return e(format_decimal($value) . ' kg/m² · ' . bmi_class((float) $value));

        case 'neuro_screen':
            $rows = '';

            foreach ((array) $value as $level => $row) {
                $cell = function (string $side) use ($row, $catalog): string {
                    if (!isset($row[$side])) {
                        return '<td class="text-muted">—</td>';
                    }

                    $is_myotome = str_starts_with($side, 'myo');
                    $text = $is_myotome ? lang('cr_neuro_grade') . ' ' . $row[$side] : ($catalog['sensation_results'][$row[$side]] ?? $row[$side]);
                    $abnormal = $is_myotome ? $row[$side] < 5 : $row[$side] !== 'normal';

                    return '<td class="' . ($abnormal ? 'text-danger fw-semibold' : '') . '">' . e($text) . '</td>';
                };

                $info = $catalog['neuro_levels'][$level] ?? ['dermatome' => '', 'myotome' => ''];
                $rows .= '<tr><td class="fw-semibold">' . e($level) . '</td>' . $cell('derm_d') . $cell('derm_e') . $cell('myo_d') . $cell('myo_e') . '</tr>';
            }

            return $rows === '' ? '' : '<div class="table-responsive"><table class="table table-sm small mb-0"><thead><tr><th>' . lang('cr_neuro_level') . '</th><th>'
                . lang('cr_neuro_derm_d') . '</th><th>' . lang('cr_neuro_derm_e') . '</th><th>' . lang('cr_neuro_myo_d') . '</th><th>'
                . lang('cr_neuro_myo_e') . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';

        case 'goals_check':
            return implode('', array_map(
                fn($id) => '<div><i class="fas fa-check-circle text-success me-1"></i>' . e($goal_labels[$id] ?? $id) . '</div>',
                (array) $value,
            ));

        default:
            return '<span style="white-space: pre-line; overflow-wrap: anywhere;">' . e((string) $value) . '</span>';
    }
};

/**
 * Campos preenchidos de um registro, agrupados por seção. Campos que saíram da ficha continuam aparecendo em
 * "Outros registros", para não esconder nada do que foi registrado.
 */
$render_content = function (array $form, array $content) use ($render_value): void {
    $known = [];

    foreach ($form as $section => $fields) {
        $rows = '';
        // Seções só de medidas (sinais vitais) ficam compactas, com os valores lado a lado.
        $compact = !array_diff(array_column($fields, 'type'), ['measure', 'blood_pressure', 'bmi']);

        foreach ($fields as $name => $field) {
            $known[] = $name;

            if ($field['type'] === 'pain_scale') {
                continue;
            }

            $html = $render_value($field, $content[$name] ?? '', $name);

            if ($html !== '') {
                $rows .= $compact
                    ? '<div><div class="small fw-semibold">' . lang('cr_field_' . $name) . '</div><div>' . $html . '</div></div>'
                    : '<dt class="small">' . lang('cr_field_' . $name) . '</dt><dd class="mb-2">' . $html . '</dd>';
            }
        }

        if ($rows !== '') {
            echo '<h6 class="text-uppercase text-muted small fw-semibold mt-3 mb-2">' . lang('cr_section_' . $section) . '</h6>';
            echo $compact ? '<div class="d-flex flex-wrap column-gap-4 row-gap-2 mb-2">' . $rows . '</div>' : '<dl class="mb-0">' . $rows . '</dl>';
        }
    }

    $legacy = '';

    foreach ($content as $name => $value) {
        if (in_array($name, $known, true) || is_array($value) || trim((string) $value) === '') {
            continue;
        }

        $label = lang('cr_field_' . $name) ?: $name;
        $legacy .= '<dt class="small">' . e($label) . '</dt><dd class="mb-2">' . $render_value(['type' => 'text'], $value) . '</dd>';
    }

    if ($legacy !== '') {
        echo '<h6 class="text-uppercase text-muted small fw-semibold mt-3 mb-2">' . lang('cr_section_other') . '</h6>';
        echo '<dl class="mb-0">' . $legacy . '</dl>';
    }
};

$render_content($rc_form, $rc_content);
