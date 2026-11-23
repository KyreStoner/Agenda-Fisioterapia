<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * Turnos de atendimento (Usuários → Turnos).
 *
 * Variáveis (html_vars): providers, provider, shifts, form, can_edit, uses_shifts, success_message, error_message.
 */
$provider = vars('provider');
$form = vars('form');
$can_edit = vars('can_edit');
$csrf_hash = get_instance()->security->get_csrf_hash();

$weekday_label = function (string $weekday): string {
    return match ($weekday) {
        'weekdays' => lang('shift_weekdays_option'),
        'all' => lang('shift_all_days_option'),
        default => lang($weekday),
    };
};

$date_label = fn(?string $date) => $date ? date('d/m/Y', strtotime($date)) : '';

$validity_label = function (array $shift) use ($date_label): string {
    if (empty($shift['valid_from']) && empty($shift['valid_until'])) {
        return lang('shift_always');
    }

    if (!empty($shift['valid_from']) && !empty($shift['valid_until'])) {
        return $date_label($shift['valid_from']) . ' – ' . $date_label($shift['valid_until']);
    }

    return !empty($shift['valid_from'])
        ? lang('shift_from') . ' ' . $date_label($shift['valid_from'])
        : lang('shift_until') . ' ' . $date_label($shift['valid_until']);
};

$time = fn(?string $value) => $value ? substr($value, 0, 5) : '';

$interval_label = fn(array $shift) => !empty($shift['break_start'])
    ? $time($shift['break_start']) . ' – ' . $time($shift['break_end'])
    : '—';

$edit_url = fn(array $shift) => site_url('provider_shifts?provider=' . $provider['id'] . '&edit=' . $shift['id']);
?>

<div id="provider-shifts-page" class="container backend-page py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom mb-3 pb-2">
        <h4 class="mb-0 fw-light"><?= lang('shifts') ?></h4>

        <?php if (vars('providers')): ?>
            <form method="get" action="<?= site_url('provider_shifts') ?>" class="flex-grow-1 flex-sm-grow-0">
                <label class="visually-hidden" for="shift-provider"><?= lang('provider') ?></label>
                <select id="shift-provider" name="provider" class="form-select" onchange="this.form.submit()">
                    <?php foreach (vars('providers') as $item): ?>
                        <option value="<?= (int) $item['id'] ?>" <?= $provider && (int) $item['id'] === (int) $provider['id'] ? 'selected' : '' ?>>
                            <?= e($item['first_name'] . ' ' . $item['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn-outline-secondary mt-2"><?= lang('select') ?></button></noscript>
            </form>
        <?php endif; ?>
    </div>

    <p class="text-muted small"><?= lang('shifts_hint') ?></p>

    <?php if (vars('success_message')): ?>
        <div class="alert alert-success" role="status"><?= e(vars('success_message')) ?></div>
    <?php endif; ?>

    <?php if (vars('error_message')): ?>
        <div class="alert alert-danger" role="alert"><?= e(vars('error_message')) ?></div>
    <?php endif; ?>

    <?php if (!$provider): ?>
        <div class="alert alert-info"><?= lang('shift_no_providers') ?></div>
    <?php else: ?>

        <?php if (!vars('shifts')): ?>
            <div class="alert alert-info"><?= lang('shift_no_shifts') ?></div>
        <?php else: ?>
            <?php // Desktop e tablet: tabela. ?>
            <div class="table-responsive d-none d-md-block mb-4">
                <table class="table table-striped align-middle small">
                    <thead>
                    <tr>
                        <th><?= lang('day') ?></th>
                        <th><?= lang('shift_hours') ?></th>
                        <th><?= lang('shift_interval') ?></th>
                        <th><?= lang('shift_minutes') ?></th>
                        <th><?= lang('shift_validity') ?></th>
                        <th><?= lang('shift_frequency') ?></th>
                        <th><?= lang('shift_notes') ?></th>
                        <?php if ($can_edit): ?><th class="text-end"><?= lang('actions') ?></th><?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (vars('shifts') as $shift): ?>
                        <tr class="<?= (int) $form['id'] === $shift['id'] ? 'table-primary' : '' ?>">
                            <td><?= $weekday_label($shift['weekday']) ?></td>
                            <td class="text-nowrap"><?= $time($shift['start_time']) ?> – <?= $time($shift['end_time']) ?></td>
                            <td class="text-nowrap"><?= $interval_label($shift) ?></td>
                            <td><?= (int) $shift['duration'] ?></td>
                            <td><?= $validity_label($shift) ?></td>
                            <td><?= lang('shift_' . $shift['frequency']) ?></td>
                            <td><?= e($shift['notes'] ?? '') ?></td>
                            <?php if ($can_edit): ?>
                                <td class="text-end text-nowrap pe-3">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= $edit_url($shift) ?>"
                                       title="<?= lang('edit') ?>" aria-label="<?= lang('edit') ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" action="<?= site_url('provider_shifts/delete') ?>" class="d-inline"
                                          onsubmit="return confirm(<?= e(json_encode(lang('shift_delete_confirm'))) ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $shift['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="<?= lang('delete') ?>"
                                                aria-label="<?= lang('delete') ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php // Celular: um cartão por turno. ?>
            <div class="d-md-none mb-4">
                <?php foreach (vars('shifts') as $shift): ?>
                    <div class="card mb-2 <?= (int) $form['id'] === $shift['id'] ? 'border-primary' : '' ?>">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold"><?= $weekday_label($shift['weekday']) ?></div>
                                    <div><?= $time($shift['start_time']) ?> – <?= $time($shift['end_time']) ?>
                                        · <?= (int) $shift['duration'] ?> <?= lang('shift_minutes') ?></div>
                                    <div class="small text-muted">
                                        <?= lang('shift_interval') ?>: <?= $interval_label($shift) ?>
                                        · <?= $validity_label($shift) ?>
                                        · <?= lang('shift_' . $shift['frequency']) ?>
                                    </div>
                                    <?php if (!empty($shift['notes'])): ?>
                                        <div class="small"><?= e($shift['notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($can_edit): ?>
                                    <div class="d-flex gap-1">
                                        <a class="btn btn-outline-secondary" href="<?= $edit_url($shift) ?>"
                                           aria-label="<?= lang('edit') ?>"><i class="fas fa-edit"></i></a>
                                        <form method="post" action="<?= site_url('provider_shifts/delete') ?>"
                                              onsubmit="return confirm(<?= e(json_encode(lang('shift_delete_confirm'))) ?>)">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $shift['id'] ?>">
                                            <button class="btn btn-outline-danger" aria-label="<?= lang('delete') ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($can_edit): ?>
            <form id="shift-form" method="post" action="<?= site_url('provider_shifts/save') ?>" class="card mb-4">
                <div class="card-body">
                    <h5 class="fw-light mb-3"><?= $form['id'] ? lang('edit_shift') : lang('add_shift') ?></h5>

                    <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
                    <input type="hidden" name="id" value="<?= e((string) ($form['id'] ?? '')) ?>">
                    <input type="hidden" name="id_users_provider" value="<?= (int) $provider['id'] ?>">

                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-4">
                            <label class="form-label" for="weekday"><?= lang('shift_weekday') ?> <span class="text-danger">*</span></label>
                            <select id="weekday" name="weekday" class="form-select" required>
                                <?php foreach (['weekdays', 'all', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $option): ?>
                                    <option value="<?= $option ?>" <?= $form['weekday'] === $option ? 'selected' : '' ?>>
                                        <?= $weekday_label($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-4">
                            <label class="form-label" for="frequency"><?= lang('shift_frequency') ?></label>
                            <select id="frequency" name="frequency" class="form-select">
                                <?php foreach (['weekly', 'biweekly'] as $option): ?>
                                    <option value="<?= $option ?>" <?= $form['frequency'] === $option ? 'selected' : '' ?>>
                                        <?= lang('shift_' . $option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label" for="duration"><?= lang('shift_duration') ?> <span class="text-danger">*</span></label>
                            <input id="duration" name="duration" type="number" inputmode="numeric" min="5" max="480" step="5"
                                   class="form-control" value="<?= e((string) $form['duration']) ?>" required>
                            <div class="form-text"><?= lang('shift_duration_hint') ?></div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="start-time"><?= lang('shift_start_time') ?> <span class="text-danger">*</span></label>
                            <input id="start-time" name="start_time" type="time" class="form-control"
                                   value="<?= e($time($form['start_time'])) ?>" required>
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="end-time"><?= lang('shift_end_time') ?> <span class="text-danger">*</span></label>
                            <input id="end-time" name="end_time" type="time" class="form-control"
                                   value="<?= e($time($form['end_time'])) ?>" required>
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="break-start"><?= lang('shift_break_start') ?></label>
                            <input id="break-start" name="break_start" type="time" class="form-control"
                                   value="<?= e($time($form['break_start'])) ?>">
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="break-end"><?= lang('shift_break_end') ?></label>
                            <input id="break-end" name="break_end" type="time" class="form-control"
                                   value="<?= e($time($form['break_end'])) ?>">
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="valid-from"><?= lang('shift_valid_from') ?></label>
                            <input id="valid-from" name="valid_from" type="date" class="form-control"
                                   value="<?= e((string) $form['valid_from']) ?>">
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="valid-until"><?= lang('shift_valid_until') ?></label>
                            <input id="valid-until" name="valid_until" type="date" class="form-control"
                                   value="<?= e((string) $form['valid_until']) ?>">
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label" for="notes"><?= lang('shift_notes') ?></label>
                            <input id="notes" name="notes" type="text" maxlength="255" class="form-control"
                                   value="<?= e((string) $form['notes']) ?>">
                        </div>

                        <div class="col-12">
                            <div class="form-text mt-0"><?= lang('shift_validity_hint') ?></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-square me-2"></i><?= lang('save') ?>
                        </button>
                        <?php if ($form['id']): ?>
                            <a class="btn btn-outline-secondary" href="<?= site_url('provider_shifts?provider=' . $provider['id']) ?>">
                                <?= lang('cancel') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php end_section('content'); ?>
