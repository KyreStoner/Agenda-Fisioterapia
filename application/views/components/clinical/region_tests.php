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

                <?php if ($area['questionnaires']): ?>
                    <div class="small text-muted mt-2 mb-1"><?= lang('cr_questionnaires') ?></div>
                    <?php foreach ($area['questionnaires'] as $questionnaire_id => $questionnaire_label): ?>
                        <div class="row g-1 align-items-center mb-1">
                            <label class="col-12 col-sm-7 col-form-label col-form-label-sm py-0"
                                   for="rq-<?= e($key . '-' . $questionnaire_id) ?>"><?= e($questionnaire_label) ?></label>
                            <div class="col-12 col-sm-5">
                                <input type="text" maxlength="50" class="form-control form-control-sm"
                                       id="rq-<?= e($key . '-' . $questionnaire_id) ?>"
                                       name="<?= e($rt_name) ?>[<?= e($key) ?>][questionnaires][<?= e($questionnaire_id) ?>]"
                                       placeholder="<?= e(lang('cr_score')) ?>"
                                       value="<?= e((string) ($rt_value[$key]['questionnaires'][$questionnaire_id] ?? '')) ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
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
