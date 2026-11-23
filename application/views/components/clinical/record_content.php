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
$render_value = function (array $field, mixed $value) use ($catalog, $goal_labels): string {
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

                foreach ($block['questionnaires'] ?? [] as $questionnaire_id => $score) {
                    $items[] = e($area['questionnaires'][$questionnaire_id] ?? $questionnaire_id) . ': <strong>' . e($score) . '</strong>';
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

        foreach ($fields as $name => $field) {
            $known[] = $name;

            if ($field['type'] === 'pain_scale') {
                continue;
            }

            $html = $render_value($field, $content[$name] ?? '');

            if ($html !== '') {
                $rows .= '<dt class="small">' . lang('cr_field_' . $name) . '</dt><dd class="mb-2">' . $html . '</dd>';
            }
        }

        if ($rows !== '') {
            echo '<h6 class="text-uppercase text-muted small fw-semibold mt-3 mb-2">' . lang('cr_section_' . $section) . '</h6>';
            echo '<dl class="mb-0">' . $rows . '</dl>';
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
