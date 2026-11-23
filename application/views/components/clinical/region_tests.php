<?php
/**
 * Testes especiais e questionários das regiões marcadas no mapa corporal (formulário da avaliação).
 *
 * Variáveis:
 *  - string $rt_name     Nome do campo (ex.: "fields[region_tests]").
 *  - string $rt_map_name Nome do campo do mapa corporal de onde vêm as regiões (ex.: "fields[pain_regions]").
 *  - array  $rt_value    Valores salvos: [bloco => ['tests' => [...], 'questionnaires' => [...]]].
 *  - array  $rt_selected Regiões marcadas.
 *  - array  $rt_blocks   Todos os blocos possíveis (área + lado), de Clinical_records_model::region_blocks().
 *  - array  $rt_catalog  Catálogo clínico.
 *
 * Todos os blocos vão para a página; só os das regiões marcadas aparecem (e só eles são salvos).
 */
$rt_region_keys = [];

foreach ($rt_catalog['regions'] as $region_id => $region) {
    if ($region['area']) {
        $rt_region_keys[$region['area'] . ($region['side'] ? '_' . $region['side'] : '')][] = $region_id;
    }
}
?>

<div class="region-tests" data-map-name="<?= e($rt_map_name) ?>">
    <p class="region-tests-empty small text-muted <?= $rt_selected ? 'd-none' : '' ?>"><?= lang('cr_region_tests_empty') ?></p>

    <?php foreach ($rt_blocks as $key => $block): ?>
        <?php
        $area = $rt_catalog['areas'][$block['area']];
        $regions = $rt_region_keys[$key] ?? [];
        $visible = (bool) array_intersect($regions, $rt_selected);
        ?>
        <div class="region-tests-block card mb-2 <?= $visible ? '' : 'd-none' ?>" data-regions="<?= e(implode(' ', $regions)) ?>">
            <div class="card-header py-2 fw-semibold"><?= e($block['label']) ?></div>
            <div class="card-body py-2">
                <?php foreach ($area['tests'] as $test_id => $test_label): ?>
                    <?php $current = $rt_value[$key]['tests'][$test_id] ?? ''; ?>
                    <div class="row g-1 align-items-center mb-1">
                        <label class="col-12 col-sm-7 col-form-label col-form-label-sm py-0"
                               for="rt-<?= e($key . '-' . $test_id) ?>"><?= e($test_label) ?></label>
                        <div class="col-12 col-sm-5">
                            <select class="form-select form-select-sm" id="rt-<?= e($key . '-' . $test_id) ?>"
                                    name="<?= e($rt_name) ?>[<?= e($key) ?>][tests][<?= e($test_id) ?>]">
                                <option value="">—</option>
                                <?php foreach ($rt_catalog['test_results'] as $result => $result_label): ?>
                                    <option value="<?= e($result) ?>" <?= $current === $result ? 'selected' : '' ?>><?= e($result_label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php $questionnaires = Clinical_records_model::area_questionnaires($block['area'], $rt_catalog); ?>
                <?php if ($questionnaires): ?>
                    <div class="small text-muted mt-2 mb-1"><?= lang('cr_questionnaires') ?></div>
                    <?php foreach ($questionnaires as $questionnaire_id => $questionnaire_label): ?>
                        <?php
                        $q_id = 'rq-' . $key . '-' . $questionnaire_id;
                        $q_form = $rt_catalog['questionnaire_forms'][$questionnaire_id] ?? null;
                        $q_answers = $rt_value[$key]['answers'][$questionnaire_id] ?? [];
                        ?>
                        <div class="questionnaire mb-2" data-scoring="<?= e($q_form['scoring'] ?? 'sum') ?>">
                            <div class="row g-1 align-items-center">
                                <label class="col-12 col-sm-7 col-form-label col-form-label-sm py-0"
                                       for="<?= e($q_id) ?>"><?= e($questionnaire_label) ?></label>
                                <div class="col-12 col-sm-5">
                                    <input type="text" maxlength="50" class="form-control form-control-sm questionnaire-score"
                                           id="<?= e($q_id) ?>"
                                           name="<?= e($rt_name) ?>[<?= e($key) ?>][questionnaires][<?= e($questionnaire_id) ?>]"
                                           placeholder="<?= e(lang('cr_score')) ?>"
                                           value="<?= e((string) ($rt_value[$key]['questionnaires'][$questionnaire_id] ?? '')) ?>">
                                </div>
                            </div>

                            <?php if ($q_form): ?>
                                <details class="mt-1" <?= $q_answers ? 'open' : '' ?>>
                                    <summary class="small text-primary">
                                        <?= lang('cr_questionnaire_answer') ?> (<?= count($q_form['questions']) ?> <?= lang('cr_questions') ?>)
                                    </summary>
                                    <div class="border rounded p-2 mt-1">
                                        <?php foreach ($q_form['questions'] as $index => $question): ?>
                                            <fieldset class="mb-2">
                                                <legend class="small fw-semibold mb-1"><?= $index + 1 ?>. <?= e($question['text']) ?></legend>
                                                <?php foreach ($question['options'] as $option_index => [$option_label, $points]): ?>
                                                    <?php $o_id = $q_id . '-' . $index . '-' . $option_index; ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input questionnaire-option" type="radio"
                                                               id="<?= e($o_id) ?>" value="<?= $option_index ?>" data-points="<?= e((string) $points) ?>"
                                                               name="<?= e($rt_name) ?>[<?= e($key) ?>][answers][<?= e($questionnaire_id) ?>][<?= $index ?>]"
                                                            <?= isset($q_answers[$index]) && (int) $q_answers[$index] === $option_index ? 'checked' : '' ?>>
                                                        <label class="form-check-label small" for="<?= e($o_id) ?>">
                                                            <?= e($option_label) ?> <span class="text-muted">(<?= e((string) $points) ?>)</span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </fieldset>
                                        <?php endforeach; ?>
                                        <div class="small fw-semibold questionnaire-total" data-questions="<?= count($q_form['questions']) ?>"></div>
                                        <?php if (!empty($q_form['interpretation'])): ?>
                                            <div class="form-text"><?= e($q_form['interpretation']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Questionários com perguntas: soma os pontos das respostas e preenche a pontuação quando todas forem respondidas.
    document.addEventListener('change', (event) => {
        if (!event.target.classList.contains('questionnaire-option')) {
            return;
        }

        const container = event.target.closest('.questionnaire');
        const total = container.querySelector('.questionnaire-total');
        const questions = Number(total.dataset.questions);
        const answered = [...container.querySelectorAll('.questionnaire-option:checked')];
        const points = answered.reduce((sum, input) => sum + Number(input.dataset.points), 0);
        const groups = {};

        container.querySelectorAll('.questionnaire-option').forEach((input) => {
            groups[input.name] = Math.max(groups[input.name] ?? 0, Number(input.dataset.points));
        });

        const max = Object.values(groups).reduce((sum, value) => sum + value, 0);

        if (answered.length < questions) {
            total.textContent = '<?= e(lang('cr_questionnaire_progress')) ?> ' + answered.length + '/' + questions;
            return;
        }

        const score = container.dataset.scoring === 'percent' ? Math.round((points / max) * 100) + '%' : points + '/' + max;
        container.querySelector('.questionnaire-score').value = score;
        total.textContent = '<?= e(lang('cr_questionnaire_total')) ?> ' + score;
    });

    // Mostra só os blocos de testes das regiões marcadas no bonequinho.
    document.addEventListener('body-map-change', (event) => {
        document.querySelectorAll('.region-tests[data-map-name="' + event.detail.name + '"]').forEach((container) => {
            let any = false;

            container.querySelectorAll('.region-tests-block').forEach((block) => {
                const visible = block.dataset.regions.split(' ').some((region) => event.detail.regions.includes(region));
                block.classList.toggle('d-none', !visible);
                any = any || visible;
            });

            container.querySelector('.region-tests-empty').classList.toggle('d-none', any);
        });
    });
</script>
