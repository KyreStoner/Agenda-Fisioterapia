<?php
/**
 * Mapa corporal da dor: bonequinho em 4 vistas (frente, costas, lado direito, lado esquerdo).
 *
 * Variáveis:
 *  - string $bm_name       Nome do campo (ex.: "fields[pain_regions]"); vazio = só leitura.
 *  - array  $bm_selected   Regiões marcadas.
 *  - array  $bm_regions    Regiões do catálogo (application/config/clinical_catalog.php).
 *  - bool   $bm_small      Versão pequena, só leitura (linha do tempo do prontuário).
 *
 * Desenho próprio e esquemático. Na frente, o lado direito do paciente fica à esquerda de quem olha (letras D/E).
 * As regiões clicáveis marcam checkboxes reais (lista "Regiões"), então funciona também sem JavaScript.
 */
$bm_interactive = !empty($bm_name);
$bm_selected = $bm_selected ?? [];
$bm_uid = 'bm-' . substr(md5(($bm_name ?? '') . uniqid('', true)), 0, 8);

// Formas: [região-base, forma, atributos]. "vl"/"vr" = lado esquerdo/direito de quem olha a figura.
$front_back = [
    ['TRONCO_SUP', 'rect', 'x="42" y="42" width="36" height="33" rx="8"'],
    ['TRONCO_INF', 'rect', 'x="43" y="76" width="34" height="28" rx="6"'],
    ['quadril@vl', 'rect', 'x="42" y="105" width="17" height="17" rx="6"'],
    ['quadril@vr', 'rect', 'x="61" y="105" width="17" height="17" rx="6"'],
    ['coxa@vl', 'rect', 'x="42" y="124" width="16" height="45" rx="7"'],
    ['coxa@vr', 'rect', 'x="62" y="124" width="16" height="45" rx="7"'],
    ['joelho@vl', 'ellipse', 'cx="50" cy="174" rx="8" ry="7"'],
    ['joelho@vr', 'ellipse', 'cx="70" cy="174" rx="8" ry="7"'],
    ['perna@vl', 'rect', 'x="43" y="182" width="14" height="44" rx="6"'],
    ['perna@vr', 'rect', 'x="63" y="182" width="14" height="44" rx="6"'],
    ['tornozelo_pe@vl', 'ellipse', 'cx="49" cy="234" rx="9" ry="8"'],
    ['tornozelo_pe@vr', 'ellipse', 'cx="71" cy="234" rx="9" ry="8"'],
    ['braco@vl', 'rect', 'x="24" y="56" width="11" height="30" rx="5"'],
    ['braco@vr', 'rect', 'x="85" y="56" width="11" height="30" rx="5"'],
    ['cotovelo@vl', 'ellipse', 'cx="29.5" cy="91" rx="6.5" ry="6"'],
    ['cotovelo@vr', 'ellipse', 'cx="90.5" cy="91" rx="6.5" ry="6"'],
    ['antebraco@vl', 'rect', 'x="22" y="97" width="11" height="26" rx="5"'],
    ['antebraco@vr', 'rect', 'x="87" y="97" width="11" height="26" rx="5"'],
    ['punho_mao@vl', 'ellipse', 'cx="26" cy="131" rx="7" ry="9"'],
    ['punho_mao@vr', 'ellipse', 'cx="94" cy="131" rx="7" ry="9"'],
    ['ombro@vl', 'ellipse', 'cx="35" cy="49" rx="10" ry="8"'],
    ['ombro@vr', 'ellipse', 'cx="85" cy="49" rx="10" ry="8"'],
    ['cervical', 'rect', 'x="53" y="32" width="14" height="10" rx="3"'],
    ['cabeca', 'ellipse', 'cx="60" cy="18" rx="13" ry="15"'],
];

// Vista lateral, de frente para a direita de quem olha (a esquerda é a mesma figura espelhada).
$lateral = [
    ['coluna_toracica', 'rect', 'x="43" y="42" width="15" height="33" rx="6"'],
    ['coluna_lombar', 'rect', 'x="43" y="76" width="15" height="28" rx="6"'],
    ['torax', 'rect', 'x="58" y="42" width="17" height="33" rx="6"'],
    ['abdome', 'rect', 'x="58" y="76" width="17" height="28" rx="6"'],
    ['quadril@s', 'rect', 'x="44" y="105" width="30" height="18" rx="8"'],
    ['coxa@s', 'rect', 'x="49" y="124" width="20" height="45" rx="8"'],
    ['joelho@s', 'ellipse', 'cx="59" cy="174" rx="9" ry="7"'],
    ['perna@s', 'rect', 'x="52" y="182" width="14" height="44" rx="6"'],
    ['tornozelo_pe@s', 'rect', 'x="50" y="228" width="26" height="11" rx="5"'],
    ['ombro@s', 'ellipse', 'cx="58" cy="50" rx="9" ry="8"'],
    ['braco@s', 'rect', 'x="53" y="56" width="10" height="30" rx="5"'],
    ['cotovelo@s', 'ellipse', 'cx="58" cy="91" rx="6.5" ry="6"'],
    ['antebraco@s', 'rect', 'x="53" y="97" width="10" height="24" rx="5"'],
    ['punho_mao@s', 'ellipse', 'cx="58" cy="129" rx="6.5" ry="8.5"'],
    ['cervical', 'rect', 'x="52" y="32" width="12" height="10" rx="3"'],
    ['cabeca', 'ellipse', 'cx="58" cy="18" rx="13" ry="15"'],
];

$views = [
    'front' => ['label' => lang('cr_view_front'), 'shapes' => $front_back, 'vl' => 'd', 'vr' => 'e', 'upper' => 'torax', 'lower' => 'abdome'],
    'back' => ['label' => lang('cr_view_back'), 'shapes' => $front_back, 'vl' => 'e', 'vr' => 'd', 'upper' => 'coluna_toracica', 'lower' => 'coluna_lombar'],
    'right' => ['label' => lang('cr_view_right'), 'shapes' => $lateral, 's' => 'd'],
    'left' => ['label' => lang('cr_view_left'), 'shapes' => $lateral, 's' => 'e', 'mirror' => true],
];

$resolve = function (string $base, array $view): string {
    if ($base === 'TRONCO_SUP') {
        return $view['upper'];
    }

    if ($base === 'TRONCO_INF') {
        return $view['lower'];
    }

    if (!str_contains($base, '@')) {
        return $base;
    }

    [$name, $position] = explode('@', $base);

    return $name . '_' . $view[$position];
};
?>

<?php if (!defined('BODY_MAP_ASSETS')): ?>
    <?php define('BODY_MAP_ASSETS', true); ?>
    <style>
        .body-map svg { width: 100%; height: auto; display: block; touch-action: manipulation; }
        .body-map .bm-region { fill: var(--bs-secondary-bg, #e9ecef); stroke: var(--bs-border-color, #adb5bd); stroke-width: 1; }
        .body-map[data-interactive] .bm-region { cursor: pointer; }
        .body-map[data-interactive] .bm-region:hover { stroke: var(--bs-primary); stroke-width: 2; }
        .body-map .bm-region.bm-selected { fill: var(--bs-danger, #dc3545); stroke: var(--bs-danger, #dc3545); }
        .body-map .bm-side { font: 600 10px sans-serif; fill: var(--bs-secondary-color, #6c757d); }
        .body-map .bm-figure { max-width: 230px; margin: 0 auto; }
        .body-map .bm-view:not(.bm-active) { display: none; }
        .body-map.bm-small .bm-view { display: block; }
        .body-map.bm-small .bm-figure { max-width: 80px; }
        @media (min-width: 992px) { .body-map .bm-view:not(.bm-active) { display: block; } }
    </style>
    <script>
        // Mapa corporal: clicar numa região marca/desmarca o checkbox dela e pinta a região em todas as vistas.
        document.addEventListener('click', (event) => {
            const shape = event.target.closest('.body-map[data-interactive] .bm-region');
            const viewButton = event.target.closest('.body-map [data-bm-view]');

            if (viewButton) {
                const map = viewButton.closest('.body-map');
                map.querySelectorAll('.bm-view').forEach((view) => view.classList.toggle('bm-active', view.dataset.view === viewButton.dataset.bmView));
                map.querySelectorAll('[data-bm-view]').forEach((button) => button.classList.toggle('active', button === viewButton));
                return;
            }

            if (!shape) {
                return;
            }

            const map = shape.closest('.body-map');
            const input = map.querySelector('.bm-input[value="' + shape.dataset.region + '"]');

            if (input) {
                input.checked = !input.checked;
                input.dispatchEvent(new Event('change', {bubbles: true}));
            }
        });

        document.addEventListener('change', (event) => {
            if (!event.target.classList.contains('bm-input')) {
                return;
            }

            const map = event.target.closest('.body-map');
            const checked = [...map.querySelectorAll('.bm-input:checked')];

            map.querySelectorAll('.bm-region[data-region="' + event.target.value + '"]').forEach((shape) => {
                shape.classList.toggle('bm-selected', event.target.checked);
            });

            map.querySelector('.bm-chips').innerHTML = checked.length
                ? checked.map((input) => '<span class="badge text-bg-danger me-1 mb-1">' + input.dataset.label + '</span>').join('')
                : '<span class="text-muted small">' + map.dataset.emptyText + '</span>';

            // Avisa quem depende das regiões (testes especiais por região).
            document.dispatchEvent(new CustomEvent('body-map-change', {detail: {name: map.dataset.name, regions: checked.map((input) => input.value)}}));
        });
    </script>
<?php endif; ?>

<div class="body-map <?= $bm_interactive ? '' : 'bm-small' ?>" id="<?= $bm_uid ?>"
     <?= $bm_interactive ? 'data-interactive data-name="' . e($bm_name) . '"' : '' ?>
     data-empty-text="<?= e(lang('cr_body_map_empty')) ?>">

    <?php if ($bm_interactive): ?>
        <div class="btn-group btn-group-sm d-flex d-lg-none mb-2" role="group" aria-label="<?= e(lang('cr_body_map_views')) ?>">
            <?php foreach ($views as $view_key => $view): ?>
                <button type="button" class="btn btn-outline-secondary <?= $view_key === 'front' ? 'active' : '' ?>"
                        data-bm-view="<?= $view_key ?>"><?= $view['label'] ?></button>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted mb-2"><?= lang('cr_body_map_hint') ?></p>
    <?php endif; ?>

    <div class="row g-2">
        <?php foreach ($views as $view_key => $view): ?>
            <div class="bm-view col-<?= $bm_interactive ? '12' : '3' ?> col-lg-3 <?= $view_key === 'front' ? 'bm-active' : '' ?>"
                 data-view="<?= $view_key ?>">
                <div class="bm-figure">
                    <svg viewBox="0 0 120 250" role="img" aria-label="<?= e($view['label']) ?>">
                        <?php if (isset($view['vl'])): ?>
                            <text class="bm-side" x="6" y="60"><?= strtoupper($view['vl']) ?></text>
                            <text class="bm-side" x="107" y="60"><?= strtoupper($view['vr']) ?></text>
                        <?php endif; ?>
                        <g <?= !empty($view['mirror']) ? 'transform="translate(120,0) scale(-1,1)"' : '' ?>>
                            <?php if (isset($view['s'])): ?>
                                <path d="M70 14 l6 5 l-6 2 z" fill="var(--bs-border-color, #adb5bd)"/>
                            <?php endif; ?>
                            <?php foreach ($view['shapes'] as [$base, $shape, $attributes]): ?>
                                <?php
                                $region = $resolve($base, $view);
                                $label = $bm_regions[$region]['label'] ?? $region;
                                ?>
                                <<?= $shape ?> class="bm-region <?= in_array($region, $bm_selected, true) ? 'bm-selected' : '' ?>"
                                    data-region="<?= e($region) ?>" <?= $attributes ?>><title><?= e($label) ?></title></<?= $shape ?>>
                            <?php endforeach; ?>
                        </g>
                    </svg>
                </div>
                <div class="text-center small text-muted"><?= $view['label'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($bm_interactive): ?>
        <div class="bm-chips mt-2" aria-live="polite">
            <?php foreach ($bm_regions as $region_id => $region): ?>
                <?php if (in_array($region_id, $bm_selected, true)): ?>
                    <span class="badge text-bg-danger me-1 mb-1"><?= e($region['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$bm_selected): ?>
                <span class="text-muted small"><?= lang('cr_body_map_empty') ?></span>
            <?php endif; ?>
        </div>

        <details class="mt-2">
            <summary class="small text-muted"><?= lang('cr_body_map_list') ?></summary>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-1 mt-1">
                <?php foreach ($bm_regions as $region_id => $region): ?>
                    <div class="col">
                        <div class="form-check">
                            <input class="form-check-input bm-input" type="checkbox" name="<?= e($bm_name) ?>[]"
                                   id="<?= $bm_uid . '-' . e($region_id) ?>" value="<?= e($region_id) ?>"
                                   data-label="<?= e($region['label']) ?>"
                                <?= in_array($region_id, $bm_selected, true) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="<?= $bm_uid . '-' . e($region_id) ?>">
                                <?= e($region['label']) ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</div>
