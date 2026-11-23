<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * Prontuário do paciente (linha do tempo de avaliações e evoluções).
 *
 * Variáveis (html_vars): customer, appointment, records, versions, access_log, forms, can_add, current_user_id,
 * success_message.
 */
$customer = vars('customer');
$appointment = vars('appointment');
$records = vars('records');
$forms = vars('forms');
$customer_id = (int) $customer['id'];

$datetime = fn(?string $value) => $value ? date('d/m/Y H:i', strtotime($value)) : '';

$query = $appointment ? '?appointment=' . (int) $appointment['id'] : '';

$has_assessment = (bool) array_filter($records, fn($record) => $record['type'] === 'assessment');

$pain_history = array_reverse(array_filter($records, fn($record) => $record['pain_scale'] !== null));

/**
 * Campos preenchidos de um registro, agrupados por seção.
 */
$catalog = vars('catalog');

/**
 * Campos preenchidos de um registro (componente compartilhado com o relatório A4).
 */
$render_content = function (array $form, array $content) use ($catalog): void {
    component('clinical/record_content', [
        'rc_form' => $form,
        'rc_content' => $content,
        'rc_catalog' => $catalog,
        'rc_goal_labels' => vars('goal_labels'),
    ]);
};
?>

<div id="clinical-record-page" class="container backend-page py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
        <div>
            <div class="text-muted small"><i class="fas fa-notes-medical me-1"></i><?= lang('clinical_record') ?></div>
            <h4 class="mb-1"><?= e(trim($customer['first_name'] . ' ' . $customer['last_name'])) ?></h4>
            <div class="small text-muted" style="overflow-wrap: anywhere;">
                <?= e($customer['phone_number'] ?: '') ?>
                <?= $customer['phone_number'] && $customer['email'] ? ' · ' : '' ?>
                <?= e($customer['email'] ?: '') ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($records): ?>
                <a class="btn btn-outline-primary btn-sm" target="_blank"
                   href="<?= site_url('clinical_records/report/' . $customer_id) ?>">
                    <i class="fas fa-print me-1"></i><?= lang('cr_report_button') ?>
                </a>
            <?php endif; ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('calendar') ?>">
                <i class="fas fa-calendar-alt me-1"></i><?= lang('cr_back_to_calendar') ?>
            </a>
        </div>
    </div>

    <p class="small text-muted mb-3"><i class="fas fa-lock me-1"></i><?= lang('cr_privacy_notice') ?></p>

    <?php if (vars('success_message')): ?>
        <div class="alert alert-success" role="status"><?= e(vars('success_message')) ?></div>
    <?php endif; ?>

    <?php if ($appointment): ?>
        <div class="alert alert-primary">
            <div class="fw-semibold">
                <?= lang('cr_appointment_context') ?> <?= $datetime($appointment['start_datetime']) ?>
                <?= $appointment['service_name'] ? '· ' . e($appointment['service_name']) : '' ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (vars('can_add')): ?>
        <div class="d-grid d-sm-flex gap-2 mb-4">
            <a class="btn <?= $has_assessment ? 'btn-outline-primary' : 'btn-primary' ?>"
               href="<?= site_url('clinical_records/create/' . $customer_id . '/assessment' . $query) ?>">
                <i class="fas fa-clipboard-list me-2"></i><?= lang('cr_new_assessment') ?>
            </a>
            <a class="btn <?= $has_assessment ? 'btn-primary' : 'btn-outline-primary' ?>"
               href="<?= site_url('clinical_records/create/' . $customer_id . '/evolution' . $query) ?>">
                <i class="fas fa-plus-square me-2"></i><?= lang('cr_new_evolution') ?>
            </a>
        </div>
    <?php endif; ?>

    <?php $progress = vars('goals_progress'); ?>
    <?php if ($progress['goals']): ?>
        <?php
        $total = count($progress['goals']);
        $done = count(array_filter($progress['goals'], fn($goal) => isset($progress['achieved'][$goal['id']])));
        $start = strtotime(date('Y-m-d', strtotime($progress['assessment']['record_date'])));
        ?>
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted"><?= lang('cr_goals_progress') ?></span>
                    <span class="fw-semibold"><?= $done ?> <?= lang('cr_goals_of') ?> <?= $total ?></span>
                </div>
                <div class="progress mb-2" role="progressbar" aria-valuenow="<?= $done ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
                    <div class="progress-bar bg-success" style="width: <?= $total ? round($done / $total * 100) : 0 ?>%"></div>
                </div>
                <ul class="list-unstyled small mb-0">
                    <?php foreach ($progress['goals'] as $goal): ?>
                        <li class="mb-1">
                            <?php if (isset($progress['achieved'][$goal['id']])): ?>
                                <i class="fas fa-check-circle text-success me-1"></i>
                            <?php else: ?>
                                <i class="far fa-circle text-muted me-1"></i>
                            <?php endif; ?>
                            <?php if ($goal['week']): ?>
                                <?php $week_start = strtotime('+' . (($goal['week'] - 1) * 7) . ' days', $start); ?>
                                <span class="text-muted"><?= lang('cr_goal_week') ?> <?= (int) $goal['week'] ?>
                                    (<?= date('d/m', $week_start) ?> <?= lang('shift_until') ?> <?= date('d/m', strtotime('+6 days', $week_start)) ?>):</span>
                            <?php endif; ?>
                            <?= e($goal['goal']) ?>
                            <?php if (isset($progress['achieved'][$goal['id']])): ?>
                                <span class="badge text-bg-success"><?= lang('cr_goal_achieved_on') ?> <?= date('d/m', strtotime($progress['achieved'][$goal['id']])) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($pain_history) > 1): ?>
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="small text-muted mb-1"><?= lang('cr_pain_trend') ?></div>
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <?php foreach (array_values($pain_history) as $index => $record): ?>
                        <?php if ($index): ?><i class="fas fa-arrow-right text-muted small"></i><?php endif; ?>
                        <span class="badge text-bg-light border" title="<?= $datetime($record['record_date']) ?>">
                            <?= (int) $record['pain_scale'] ?>
                            <span class="fw-normal text-muted"><?= date('d/m', strtotime($record['record_date'])) ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$records): ?>
        <div class="alert alert-info"><?= lang('cr_no_records') ?></div>
    <?php endif; ?>

    <?php foreach ($records as $index => $record): ?>
        <?php $is_assessment = $record['type'] === 'assessment'; ?>
        <div class="card mb-3 <?= $is_assessment ? 'border-primary' : '' ?>">
            <details <?= $index === 0 || $is_assessment ? 'open' : '' ?>>
                <summary class="card-header bg-transparent d-flex flex-wrap align-items-center gap-2" style="cursor: pointer;">
                    <span class="badge <?= $is_assessment ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                        <?= lang('cr_' . $record['type']) ?>
                    </span>
                    <span class="fw-semibold"><?= $datetime($record['record_date']) ?></span>
                    <?php if ($record['pain_scale'] !== null): ?>
                        <span class="badge text-bg-light border"><?= lang('cr_pain') ?> <?= (int) $record['pain_scale'] ?>/10</span>
                    <?php endif; ?>
                    <span class="small text-muted ms-sm-auto"><?= lang('cr_by') ?> <?= e($record['author_name']) ?></span>
                </summary>

                <div class="card-body pt-1">
                    <?php $render_content($forms[$record['type']], $record['content']); ?>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <div class="small text-muted">
                            <?= lang('cr_registered_at') ?> <?= $datetime($record['create_datetime']) ?>
                            <?php if ($record['update_datetime'] !== $record['create_datetime']): ?>
                                · <?= lang('cr_updated_at') ?> <?= $datetime($record['update_datetime']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-secondary" target="_blank"
                               href="<?= site_url('clinical_records/report/' . $customer_id . '?record=' . $record['id']) ?>">
                                <i class="fas fa-print me-1"></i><?= lang('cr_print') ?>
                            </a>
                            <?php if ($record['id_users_author'] === vars('current_user_id')): ?>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= site_url('clinical_records/edit/' . $record['id']) ?>">
                                    <i class="fas fa-edit me-1"></i><?= lang('edit') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty(vars('versions')[$record['id']])): ?>
                        <details class="mt-2">
                            <summary class="small text-muted">
                                <?= lang('cr_previous_versions') ?> (<?= count(vars('versions')[$record['id']]) ?>)
                            </summary>
                            <?php foreach (vars('versions')[$record['id']] as $version): ?>
                                <div class="border-start border-3 ps-2 mt-2 small">
                                    <div class="text-muted">
                                        <?= lang('cr_replaced_at') ?> <?= $datetime($version['create_datetime']) ?>
                                        · <?= e($version['user_name']) ?>
                                        <?php if ($version['pain_scale'] !== null): ?>
                                            · <?= lang('cr_pain') ?> <?= (int) $version['pain_scale'] ?>/10
                                        <?php endif; ?>
                                    </div>
                                    <?php $render_content($forms[$record['type']], $version['content']); ?>
                                </div>
                            <?php endforeach; ?>
                        </details>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    <?php endforeach; ?>

    <details class="mt-4 mb-3">
        <summary class="text-muted"><i class="fas fa-shield-alt me-1"></i><?= lang('cr_access_log') ?></summary>
        <div class="table-responsive mt-2">
            <table class="table table-sm small">
                <thead>
                <tr>
                    <th><?= lang('cr_date_time') ?></th>
                    <th><?= lang('cr_user') ?></th>
                    <th><?= lang('cr_action') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (vars('access_log') as $entry): ?>
                    <tr>
                        <td class="text-nowrap"><?= $datetime($entry['create_datetime']) ?></td>
                        <td><?= e($entry['user_name']) ?></td>
                        <td><?= lang('cr_action_' . $entry['action']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>

<?php end_section('content'); ?>
