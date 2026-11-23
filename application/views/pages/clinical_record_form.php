<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * Formulário de avaliação ou evolução do prontuário.
 *
 * Variáveis (html_vars): customer, record, form, error_message.
 */
$customer = vars('customer');
$record = vars('record');
$content = $record['content'] ?? [];
$csrf_hash = get_instance()->security->get_csrf_hash();
$record_date = date('Y-m-d\TH:i', strtotime($record['record_date']));
$back_url = site_url('clinical_records/patient/' . (int) $customer['id']);
?>

<div id="clinical-record-form-page" class="container backend-page py-3">
    <div class="mb-3">
        <div class="text-muted small"><i class="fas fa-notes-medical me-1"></i><?= lang('clinical_record') ?></div>
        <h4 class="mb-0"><?= vars('page_title') ?></h4>
        <div class="text-muted"><?= e(trim($customer['first_name'] . ' ' . $customer['last_name'])) ?></div>
    </div>

    <?php if (vars('error_message')): ?>
        <div class="alert alert-danger" role="alert"><?= e(vars('error_message')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('clinical_records/save') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
        <input type="hidden" name="id" value="<?= (int) ($record['id'] ?? 0) ?: '' ?>">
        <input type="hidden" name="type" value="<?= e($record['type']) ?>">
        <input type="hidden" name="id_users_customer" value="<?= (int) $customer['id'] ?>">
        <input type="hidden" name="id_appointments" value="<?= (int) ($record['id_appointments'] ?? 0) ?: '' ?>">

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label" for="record-date"><?= lang('cr_record_date') ?> <span class="text-danger">*</span></label>
                <input id="record-date" name="record_date" type="datetime-local" class="form-control" style="max-width: 16rem;"
                       value="<?= e($record_date) ?>" required>
            </div>
        </div>

        <?php foreach (vars('form') as $section => $fields): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-light mb-3"><?= lang('cr_section_' . $section) ?></h5>

                    <?php foreach ($fields as $name => $field): ?>
                        <?php
                        $id = 'field-' . str_replace('_', '-', $name);
                        $value = (string) ($content[$name] ?? '');
                        $required = !empty($field['required']);
                        ?>
                        <div class="mb-3">
                            <?php if ($field['type'] === 'pain_scale'): ?>
                                <fieldset>
                                    <legend class="form-label fs-6"><?= lang('cr_field_' . $name) ?></legend>
                                    <div class="d-flex flex-wrap gap-1" role="radiogroup">
                                        <?php foreach (range(0, 10) as $score): ?>
                                            <input type="radio" class="btn-check" name="fields[<?= $name ?>]"
                                                   id="<?= $id . '-' . $score ?>" value="<?= $score ?>"
                                                <?= $value !== '' && (int) $value === $score ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-primary" style="min-width: 2.75rem;"
                                                   for="<?= $id . '-' . $score ?>"><?= $score ?></label>
                                        <?php endforeach; ?>
                                        <input type="radio" class="btn-check" name="fields[<?= $name ?>]"
                                               id="<?= $id ?>-none" value="" <?= $value === '' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary" for="<?= $id ?>-none"><?= lang('cr_pain_not_assessed') ?></label>
                                    </div>
                                    <div class="form-text"><?= lang('cr_pain_scale_hint') ?></div>
                                </fieldset>
                            <?php else: ?>
                                <label class="form-label" for="<?= $id ?>">
                                    <?= lang('cr_field_' . $name) ?>
                                    <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea id="<?= $id ?>" name="fields[<?= $name ?>]" class="form-control" rows="3"
                                              maxlength="20000" <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>
                                <?php elseif ($field['type'] === 'number'): ?>
                                    <input id="<?= $id ?>" name="fields[<?= $name ?>]" type="number" inputmode="numeric"
                                           min="0" max="999" class="form-control" style="max-width: 10rem;"
                                           value="<?= e($value) ?>" <?= $required ? 'required' : '' ?>>
                                <?php else: ?>
                                    <input id="<?= $id ?>" name="fields[<?= $name ?>]" type="text" maxlength="500"
                                           class="form-control" value="<?= e($value) ?>" <?= $required ? 'required' : '' ?>>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <p class="small text-muted"><i class="fas fa-lock me-1"></i><?= lang('cr_privacy_notice') ?></p>

        <div class="d-grid d-sm-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check-square me-2"></i><?= lang('save') ?>
            </button>
            <a class="btn btn-outline-secondary" href="<?= $back_url ?>"><?= lang('cr_back_to_record') ?></a>
        </div>
    </form>
</div>

<?php end_section('content'); ?>
