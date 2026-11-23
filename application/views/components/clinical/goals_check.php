<?php
/**
 * Metas da avaliação na evolução: marca as que foram atingidas nesta sessão.
 *
 * Variáveis:
 *  - string $gc_name     Nome do campo (ex.: "fields[goals_achieved]").
 *  - array  $gc_goals    Metas da avaliação mais recente.
 *  - array  $gc_achieved Metas já atingidas em outras evoluções: [id => data].
 *  - array  $gc_value    Metas marcadas nesta evolução.
 */
?>

<?php if (!$gc_goals): ?>
    <p class="small text-muted mb-0"><?= lang('cr_goals_none') ?></p>
<?php else: ?>
    <ul class="list-unstyled mb-0">
        <?php foreach ($gc_goals as $goal): ?>
            <li class="mb-2">
                <?php if (isset($gc_achieved[$goal['id']])): ?>
                    <span class="text-success"><i class="fas fa-check-circle me-1"></i></span>
                    <?php if ($goal['week']): ?><span class="text-muted small"><?= lang('cr_goal_week') ?> <?= (int) $goal['week'] ?> ·</span><?php endif; ?>
                    <?= e($goal['goal']) ?>
                    <span class="badge text-bg-success ms-1">
                        <?= lang('cr_goal_achieved_on') ?> <?= date('d/m', strtotime($gc_achieved[$goal['id']])) ?>
                    </span>
                <?php else: ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="<?= e($gc_name) ?>[]" value="<?= e($goal['id']) ?>"
                               id="gc-<?= e($goal['id']) ?>" <?= in_array($goal['id'], $gc_value, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="gc-<?= e($goal['id']) ?>">
                            <?php if ($goal['week']): ?><span class="text-muted small"><?= lang('cr_goal_week') ?> <?= (int) $goal['week'] ?> ·</span><?php endif; ?>
                            <?= e($goal['goal']) ?>
                        </label>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="form-text"><?= lang('cr_goals_check_hint') ?></div>
<?php endif; ?>
