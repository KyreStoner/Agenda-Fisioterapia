<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * Metas padrão do tratamento (lista + formulário).
 *
 * Variáveis (html_vars): templates, areas, form, success_message, error_message.
 */
$form = vars('form');
$areas = vars('areas');
$csrf_hash = get_instance()->security->get_csrf_hash();
$area_label = fn(?string $area) => $area ? ($areas[$area]['label'] ?? $area) : lang('goal_template_general');
?>

<div id="goal-templates-page" class="container backend-page py-3">
    <div class="border-bottom mb-3 pb-2">
        <h4 class="mb-0 fw-light"><?= lang('goal_templates') ?></h4>
    </div>

    <p class="text-muted small"><?= lang('goal_templates_hint') ?></p>

    <?php if (vars('success_message')): ?>
        <div class="alert alert-success" role="status"><?= e(vars('success_message')) ?></div>
    <?php endif; ?>

    <?php if (vars('error_message')): ?>
        <div class="alert alert-danger" role="alert"><?= e(vars('error_message')) ?></div>
    <?php endif; ?>

    <?php if (!vars('templates')): ?>
        <div class="alert alert-info"><?= lang('goal_templates_empty') ?></div>
    <?php endif; ?>

    <?php $current_area = false; ?>
    <?php foreach (vars('templates') as $template): ?>
        <?php if ($template['area'] !== $current_area): ?>
            <?php $current_area = $template['area']; ?>
            <h6 class="text-uppercase text-muted small fw-semibold mt-3 mb-2"><?= e($area_label($template['area'])) ?></h6>
        <?php endif; ?>
        <div class="card mb-2 <?= (int) $form['id'] === $template['id'] ? 'border-primary' : '' ?>">
            <div class="card-body py-2 d-flex justify-content-between align-items-start gap-2">
                <div style="overflow-wrap: anywhere;">
                    <div class="fw-semibold"><?= e($template['goal']) ?></div>
                    <?php if ($template['plan']): ?>
                        <div class="small text-muted"><?= e($template['plan']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('goal_templates?edit=' . $template['id']) ?>"
                       aria-label="<?= lang('edit') ?>"><i class="fas fa-edit"></i></a>
                    <form method="post" action="<?= site_url('goal_templates/delete') ?>"
                          onsubmit="return confirm(<?= e(json_encode(lang('goal_template_delete_confirm'))) ?>)">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
                        <input type="hidden" name="id" value="<?= (int) $template['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" aria-label="<?= lang('delete') ?>"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <form method="post" action="<?= site_url('goal_templates/save') ?>" class="card mt-4 mb-4">
        <div class="card-body">
            <h5 class="fw-light mb-3"><?= $form['id'] ? lang('goal_template_edit') : lang('goal_template_add') ?></h5>
            <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
            <input type="hidden" name="id" value="<?= e((string) ($form['id'] ?? '')) ?>">

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="gt-area"><?= lang('goal_template_area') ?></label>
                    <select id="gt-area" name="area" class="form-select">
                        <option value=""><?= lang('goal_template_general') ?></option>
                        <?php foreach ($areas as $area_id => $area): ?>
                            <option value="<?= e($area_id) ?>" <?= ($form['area'] ?? '') === $area_id ? 'selected' : '' ?>><?= e($area['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="gt-goal"><?= lang('cr_goal') ?> <span class="text-danger">*</span></label>
                    <input id="gt-goal" name="goal" type="text" maxlength="500" class="form-control" required
                           value="<?= e((string) $form['goal']) ?>" placeholder="<?= e(lang('cr_goal_placeholder')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="gt-plan"><?= lang('cr_goal_plan') ?></label>
                    <textarea id="gt-plan" name="plan" rows="2" maxlength="2000" class="form-control"><?= e((string) $form['plan']) ?></textarea>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check-square me-2"></i><?= lang('save') ?></button>
                <?php if ($form['id']): ?>
                    <a class="btn btn-outline-secondary" href="<?= site_url('goal_templates') ?>"><?= lang('cancel') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php end_section('content'); ?>
