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
$render_content = function (array $form, array $content): void {
    foreach ($form as $section => $fields) {
        $filled = array_filter(
            array_keys($fields),
            fn($name) => $fields[$name]['type'] !== 'pain_scale' && ($content[$name] ?? '') !== '',
        );

        if (!$filled) {
            continue;
        }

        echo '<h6 class="text-uppercase text-muted small fw-semibold mt-3 mb-2">' . lang('cr_section_' . $section) . '</h6>';
        echo '<dl class="mb-0">';

        foreach ($filled as $name) {
            echo '<dt class="small">' . lang('cr_field_' . $name) . '</dt>';
            echo '<dd class="mb-2" style="white-space: pre-line; overflow-wrap: anywhere;">' . e((string) $content[$name]) . '</dd>';
        }

        echo '</dl>';
    }
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
        <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('calendar') ?>">
            <i class="fas fa-calendar-alt me-1"></i><?= lang('cr_back_to_calendar') ?>
        </a>
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
                        <?php if ($record['id_users_author'] === vars('current_user_id')): ?>
                            <a class="btn btn-sm btn-outline-secondary"
                               href="<?= site_url('clinical_records/edit/' . $record['id']) ?>">
                                <i class="fas fa-edit me-1"></i><?= lang('edit') ?>
                            </a>
                        <?php endif; ?>
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
