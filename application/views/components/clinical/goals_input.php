<?php
/**
 * Metas por semana (avaliação): semana, meta e como atingir. O plano de tratamento segue as metas.
 *
 * Variáveis:
 *  - string $gi_name  Nome do campo (ex.: "fields[goals]").
 *  - array  $gi_value Metas salvas: [['id', 'week', 'goal', 'plan'], ...].
 */
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

<div class="goals-input">
    <p class="small text-muted mb-2"><?= lang('cr_goals_hint') ?></p>
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
