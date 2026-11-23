<?php
/**
 * Metas por semana (avaliação): semana, meta e como atingir. O plano de tratamento segue as metas.
 *
 * Variáveis:
 *  - string $gi_name  Nome do campo (ex.: "fields[goals]").
 *  - array  $gi_value Metas salvas: [['id', 'week', 'goal', 'plan'], ...].
 *  - array  $gi_templates Metas padrão cadastradas (Goal_templates_model::get_all()).
 *  - string $gi_map_name  Nome do campo do mapa corporal; a lista de metas padrão mostra as gerais e as das áreas
 *                         marcadas nele.
 *  - array  $gi_catalog   Catálogo clínico (para ligar região → área).
 */
$gi_region_areas = array_filter(array_map(fn($region) => $region['area'], $gi_catalog['regions'] ?? []));
$gi_template_data = array_map(
    fn($template) => ['area' => $template['area'], 'goal' => $template['goal'], 'plan' => (string) $template['plan']],
    $gi_templates ?? [],
);
$gi_rows = $gi_value ?: [['id' => '', 'week' => 1, 'goal' => '', 'plan' => ''], ['id' => '', 'week' => 2, 'goal' => '', 'plan' => '']];

$gi_row = function (string $index, array $goal) use ($gi_name): string {
    ob_start(); ?>
    <div class="goal-row border rounded p-2 mb-2">
        <input type="hidden" name="<?= e($gi_name) ?>[<?= $index ?>][id]" value="<?= e((string) ($goal['id'] ?? '')) ?>">
        <div class="row g-2">
            <div class="col-4 col-sm-2">
                <label class="form-label small mb-1"><?= lang('cr_goal_week') ?></label>
                <input type="number" inputmode="numeric" min="1" max="104" class="form-control form-control-sm"
                       name="<?= e($gi_name) ?>[<?= $index ?>][week]" value="<?= e((string) ($goal['week'] ?? '')) ?>">
            </div>
            <div class="col-8 col-sm-10">
                <label class="form-label small mb-1"><?= lang('cr_goal') ?></label>
                <div class="input-group input-group-sm">
                    <input type="text" maxlength="500" class="form-control" name="<?= e($gi_name) ?>[<?= $index ?>][goal]"
                           value="<?= e((string) ($goal['goal'] ?? '')) ?>" placeholder="<?= e(lang('cr_goal_placeholder')) ?>">
                    <button type="button" class="btn btn-outline-danger goal-remove" aria-label="<?= e(lang('delete')) ?>">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 goal-template-col d-none">
                <select class="form-select form-select-sm goal-template-select" aria-label="<?= e(lang('goal_template_choose')) ?>">
                    <option value=""><?= lang('goal_template_choose') ?></option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1"><?= lang('cr_goal_plan') ?></label>
                <textarea class="form-control form-control-sm" rows="2" maxlength="2000"
                          name="<?= e($gi_name) ?>[<?= $index ?>][plan]"><?= e((string) ($goal['plan'] ?? '')) ?></textarea>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
};
?>

<div class="goals-input" data-map-name="<?= e($gi_map_name ?? '') ?>"
     data-templates="<?= e(json_encode(array_values($gi_template_data), JSON_UNESCAPED_UNICODE)) ?>"
     data-region-areas="<?= e(json_encode($gi_region_areas)) ?>">
    <p class="small text-muted mb-2">
        <?= lang('cr_goals_hint') ?>
        <a href="<?= site_url('goal_templates') ?>" target="_blank"><?= lang('goal_templates_manage') ?></a>
    </p>
    <div class="goal-rows">
        <?php foreach (array_values($gi_rows) as $index => $goal): ?>
            <?= $gi_row((string) $index, $goal) ?>
        <?php endforeach; ?>
    </div>
    <template class="goal-template"><?= $gi_row('__INDEX__', ['id' => '', 'week' => '', 'goal' => '', 'plan' => '']) ?></template>
    <button type="button" class="btn btn-sm btn-outline-primary goal-add">
        <i class="fas fa-plus me-1"></i><?= lang('cr_goal_add') ?>
    </button>
</div>

<?php if (!defined('GOALS_INPUT_ASSETS')): ?>
    <?php define('GOALS_INPUT_ASSETS', true); ?>
    <script>
        // Metas padrão: cada linha tem uma lista com as metas gerais e as das áreas marcadas no bonequinho.
        function fillTemplateSelects(container, regions) {
            const templates = JSON.parse(container.dataset.templates);
            const regionAreas = JSON.parse(container.dataset.regionAreas);

            if (!regions) {
                regions = [...document.querySelectorAll('.bm-input:checked')]
                    .filter((input) => input.name === container.dataset.mapName + '[]')
                    .map((input) => input.value);
            }

            const areas = regions.map((region) => regionAreas[region]).filter(Boolean);
            const available = templates.filter((template) => !template.area || areas.includes(template.area));

            container.querySelectorAll('.goal-template-select').forEach((select) => {
                const placeholder = select.options[0];
                select.replaceChildren(placeholder);
                available.forEach((template, index) => {
                    const option = new Option(template.goal, String(index));
                    option.dataset.plan = template.plan;
                    select.add(option);
                });
                select.closest('.goal-template-col').classList.toggle('d-none', available.length === 0);
            });
        }

        document.querySelectorAll('.goals-input').forEach((container) => fillTemplateSelects(container));

        document.addEventListener('body-map-change', (event) => {
            document.querySelectorAll('.goals-input[data-map-name="' + event.detail.name + '"]').forEach((container) => {
                fillTemplateSelects(container, event.detail.regions);
            });
        });

        document.addEventListener('change', (event) => {
            if (!event.target.classList.contains('goal-template-select') || !event.target.value) {
                return;
            }

            const option = event.target.selectedOptions[0];
            const row = event.target.closest('.goal-row');
            row.querySelector('input[name$="[goal]"]').value = option.text;
            const plan = row.querySelector('textarea[name$="[plan]"]');

            if (!plan.value.trim()) {
                plan.value = option.dataset.plan;
            }

            event.target.value = '';
        });

        // Adiciona e remove linhas de metas.
        document.addEventListener('click', (event) => {
            const add = event.target.closest('.goals-input .goal-add');
            const remove = event.target.closest('.goals-input .goal-remove');

            if (add) {
                const container = add.closest('.goals-input');
                const rows = container.querySelector('.goal-rows');
                const html = container.querySelector('.goal-template').innerHTML.replaceAll('__INDEX__', 'n' + Date.now());
                const weeks = [...rows.querySelectorAll('input[name$="[week]"]')].map((input) => Number(input.value) || 0);

                rows.insertAdjacentHTML('beforeend', html);
                fillTemplateSelects(container);
                rows.lastElementChild.querySelector('input[name$="[week]"]').value = Math.max(0, ...weeks) + 1;
                rows.lastElementChild.querySelector('input[name$="[goal]"]').focus();
            }

            if (remove) {
                const row = remove.closest('.goal-row');
                const rows = row.parentElement;

                if (rows.children.length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input:not([type=hidden]), textarea').forEach((input) => (input.value = ''));
                }
            }
        });
    </script>
<?php endif; ?>
