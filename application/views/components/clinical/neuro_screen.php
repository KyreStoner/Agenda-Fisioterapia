<?php
/**
 * Exame neurológico: dermátomos (sensibilidade) e miótomos (força 0 a 5), lado direito e esquerdo.
 * Os níveis aparecem conforme as regiões marcadas no mapa corporal: membro superior/cervical (C4 a T1) e
 * membro inferior/lombar (L2 a S1).
 *
 * Variáveis:
 *  - string $ns_name     Nome do campo (ex.: "fields[neuro_screen]").
 *  - string $ns_map_name Nome do campo do mapa corporal (ex.: "fields[pain_regions]").
 *  - array  $ns_value    Valores salvos: [nível => ['derm_d', 'derm_e', 'myo_d', 'myo_e']].
 *  - array  $ns_selected Regiões marcadas.
 *  - array  $ns_catalog  Catálogo clínico.
 */
$ns_quadrants = Clinical_records_model::neuro_quadrants($ns_selected, $ns_catalog);

// Região → metade do corpo, para o JavaScript mostrar os níveis certos quando o bonequinho muda.
$ns_region_quadrants = [];

foreach ($ns_catalog['regions'] as $region_id => $region) {
    $quadrant = $region['area'] ? $ns_catalog['areas'][$region['area']]['quadrant'] ?? null : null;

    if ($quadrant) {
        $ns_region_quadrants[$region_id] = $quadrant;
    }
}

$ns_groups = ['upper' => lang('cr_neuro_upper'), 'lower' => lang('cr_neuro_lower')];
?>

<div class="neuro-screen" data-map-name="<?= e($ns_map_name) ?>"
     data-region-quadrants="<?= e(json_encode($ns_region_quadrants)) ?>">
    <p class="neuro-screen-empty small text-muted <?= $ns_quadrants ? 'd-none' : '' ?>"><?= lang('cr_neuro_empty') ?></p>

    <?php foreach ($ns_groups as $quadrant => $group_label): ?>
        <div class="neuro-screen-group card mb-2 <?= in_array($quadrant, $ns_quadrants, true) ? '' : 'd-none' ?>"
             data-quadrant="<?= $quadrant ?>">
            <div class="card-header py-2 fw-semibold"><?= e($group_label) ?></div>
            <div class="card-body py-2">
                <?php foreach ($ns_catalog['neuro_levels'] as $level => $info): ?>
                    <?php if ($info['quadrant'] !== $quadrant) {
                        continue;
                    } ?>
                    <div class="row g-1 align-items-center border-bottom py-1">
                        <div class="col-12 col-lg-4 small">
                            <span class="fw-semibold"><?= e($level) ?></span>
                            <span class="text-muted">· <?= e($info['dermatome']) ?> · <?= e($info['myotome']) ?></span>
                        </div>
                        <?php foreach (['derm_d', 'derm_e'] as $side): ?>
                            <div class="col-6 col-lg-2">
                                <select class="form-select form-select-sm" name="<?= e($ns_name) ?>[<?= e($level) ?>][<?= $side ?>]"
                                        aria-label="<?= e($level . ' ' . lang('cr_neuro_' . $side)) ?>">
                                    <option value=""><?= lang('cr_neuro_' . $side) ?></option>
                                    <?php foreach ($ns_catalog['sensation_results'] as $result => $result_label): ?>
                                        <option value="<?= e($result) ?>" <?= ($ns_value[$level][$side] ?? '') === $result ? 'selected' : '' ?>>
                                            <?= e($result_label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach (['myo_d', 'myo_e'] as $side): ?>
                            <div class="col-6 col-lg-2">
                                <select class="form-select form-select-sm" name="<?= e($ns_name) ?>[<?= e($level) ?>][<?= $side ?>]"
                                        aria-label="<?= e($level . ' ' . lang('cr_neuro_' . $side)) ?>">
                                    <option value=""><?= lang('cr_neuro_' . $side) ?></option>
                                    <?php foreach (range(5, 0) as $grade): ?>
                                        <option value="<?= $grade ?>" <?= (string) ($ns_value[$level][$side] ?? '') === (string) $grade ? 'selected' : '' ?>>
                                            <?= lang('cr_neuro_grade') ?> <?= $grade ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="form-text"><?= lang('cr_neuro_hint') ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Mostra os níveis (superior/inferior) conforme as regiões marcadas no bonequinho.
    document.addEventListener('body-map-change', (event) => {
        document.querySelectorAll('.neuro-screen[data-map-name="' + event.detail.name + '"]').forEach((container) => {
            const map = JSON.parse(container.dataset.regionQuadrants);
            const quadrants = event.detail.regions.map((region) => map[region]).filter(Boolean);

            container.querySelectorAll('.neuro-screen-group').forEach((group) => {
                group.classList.toggle('d-none', !quadrants.includes(group.dataset.quadrant));
            });

            container.querySelector('.neuro-screen-empty').classList.toggle('d-none', quadrants.length > 0);
        });
    });
</script>
