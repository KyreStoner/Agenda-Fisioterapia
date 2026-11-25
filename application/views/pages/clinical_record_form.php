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
        <input type="hidden" name="record_type" value="<?= (int) ($record['id_clinical_record_types'] ?? 0) ?>">
        <input type="hidden" name="id_users_customer" value="<?= (int) $customer['id'] ?>">
        <input type="hidden" name="id_appointments" value="<?= (int) ($record['id_appointments'] ?? 0) ?: '' ?>">

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label" for="record-date"><?= lang('cr_record_date') ?> <span class="text-danger">*</span></label>
                <input id="record-date" name="record_date" type="datetime-local" class="form-control" style="max-width: 16rem;"
                       value="<?= e($record_date) ?>" required>
            </div>
        </div>

        <?php
        // Largura de cada tipo de campo na grade (os sinais vitais ficam lado a lado).
        $column = fn(string $type) => match ($type) {
            'measure', 'blood_pressure', 'bmi' => 'col-6 col-md-4 col-lg-3',
            'choice', 'text' => 'col-12 col-md-6',
            'number' => 'col-6 col-md-4',
            default => 'col-12',
        };
        ?>
        <?php foreach (vars('form') as $section => $fields): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-light mb-3"><?= lang('cr_section_' . $section) ?></h5>

                    <div class="row g-3">
                    <?php foreach ($fields as $name => $field): ?>
                        <?php
                        $id = 'field-' . str_replace('_', '-', $name);
                        $value = is_array($content[$name] ?? null) ? '' : (string) ($content[$name] ?? '');
                        $required = !empty($field['required']);
                        ?>
                        <div class="<?= $column($field['type']) ?>">
                            <?php if ($field['type'] === 'body_map'): ?>
                                <div class="form-label"><?= lang('cr_field_' . $name) ?></div>
                                <?php component('clinical/body_map', [
                                    'bm_name' => 'fields[' . $name . ']',
                                    'bm_selected' => array_values((array) ($content[$name] ?? [])),
                                    'bm_regions' => vars('catalog')['regions'],
                                    'bm_small' => false,
                                ]); ?>
                            <?php elseif ($field['type'] === 'region_tests'): ?>
                                <div class="form-label"><?= lang('cr_field_' . $name) ?></div>
                                <?php component('clinical/region_tests', [
                                    'rt_name' => 'fields[' . $name . ']',
                                    'rt_map_name' => 'fields[' . $field['regions_field'] . ']',
                                    'rt_value' => (array) ($content[$name] ?? []),
                                    'rt_selected' => array_values((array) ($content[$field['regions_field']] ?? [])),
                                    'rt_blocks' => vars('region_blocks'),
                                    'rt_catalog' => vars('catalog'),
                                ]); ?>
                            <?php elseif ($field['type'] === 'neuro_screen'): ?>
                                <div class="form-label"><?= lang('cr_field_' . $name) ?></div>
                                <?php component('clinical/neuro_screen', [
                                    'ns_name' => 'fields[' . $name . ']',
                                    'ns_map_name' => 'fields[' . $field['regions_field'] . ']',
                                    'ns_value' => (array) ($content[$name] ?? []),
                                    'ns_selected' => array_values((array) ($content[$field['regions_field']] ?? [])),
                                    'ns_catalog' => vars('catalog'),
                                ]); ?>
                            <?php elseif ($field['type'] === 'goals'): ?>
                                <div class="form-label">
                                    <?= lang('cr_field_' . $name) ?>
                                    <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
                                </div>
                                <?php component('clinical/goals_input', [
                                    'gi_name' => 'fields[' . $name . ']',
                                    'gi_value' => (array) ($content[$name] ?? []),
                                    'gi_templates' => vars('goal_templates') ?? [],
                                    'gi_map_name' => 'fields[' . ($field['regions_field'] ?? '') . ']',
                                    'gi_catalog' => vars('catalog'),
                                ]); ?>
                            <?php elseif ($field['type'] === 'goals_check'): ?>
                                <div class="form-label"><?= lang('cr_field_' . $name) ?></div>
                                <?php component('clinical/goals_check', [
                                    'gc_name' => 'fields[' . $name . ']',
                                    'gc_goals' => vars('goals_progress')['goals'] ?? [],
                                    'gc_achieved' => vars('goals_progress')['achieved'] ?? [],
                                    'gc_value' => array_values((array) ($content[$name] ?? [])),
                                ]); ?>
                            <?php elseif ($field['type'] === 'pain_scale'): ?>
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
                            <?php elseif ($field['type'] === 'choice'): ?>
                                <fieldset>
                                    <legend class="form-label fs-6"><?= lang('cr_field_' . $name) ?></legend>
                                    <div class="d-flex flex-wrap gap-1" role="radiogroup">
                                        <?php foreach ($field['options'] as $option): ?>
                                            <input type="radio" class="btn-check" name="fields[<?= $name ?>]"
                                                   id="<?= $id . '-' . $option ?>" value="<?= $option ?>"
                                                <?= $value === $option ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-primary"
                                                   for="<?= $id . '-' . $option ?>"><?= lang('cr_opt_' . $name . '_' . $option) ?></label>
                                        <?php endforeach; ?>
                                        <input type="radio" class="btn-check" name="fields[<?= $name ?>]"
                                               id="<?= $id ?>-none" value="" <?= $value === '' ? 'checked' : '' ?>>
                                        <label class="btn btn-sm btn-outline-secondary" for="<?= $id ?>-none"><?= lang('cr_not_informed') ?></label>
                                    </div>
                                </fieldset>
                            <?php elseif ($field['type'] === 'measure'): ?>
                                <label class="form-label" for="<?= $id ?>"><?= lang('cr_field_' . $name) ?></label>
                                <div class="input-group">
                                    <input id="<?= $id ?>" name="fields[<?= $name ?>]" type="number"
                                           inputmode="<?= $field['step'] < 1 ? 'decimal' : 'numeric' ?>"
                                           min="<?= $field['min'] ?>" max="<?= $field['max'] ?>" step="<?= $field['step'] ?>"
                                           class="form-control" value="<?= e($value) ?>" data-measure="<?= $name ?>">
                                    <span class="input-group-text"><?= e($field['unit']) ?></span>
                                </div>
                            <?php elseif ($field['type'] === 'blood_pressure'): ?>
                                <?php $bp = (array) ($content[$name] ?? []); ?>
                                <label class="form-label" for="<?= $id ?>-sys"><?= lang('cr_field_' . $name) ?></label>
                                <div class="input-group">
                                    <input id="<?= $id ?>-sys" name="fields[<?= $name ?>][sys]" type="number" inputmode="numeric"
                                           min="50" max="300" class="form-control px-2" placeholder="120"
                                           aria-label="<?= e(lang('cr_systolic')) ?>" value="<?= e((string) ($bp['sys'] ?? '')) ?>">
                                    <span class="input-group-text px-1">/</span>
                                    <input name="fields[<?= $name ?>][dia]" type="number" inputmode="numeric" min="20" max="200"
                                           class="form-control px-2" placeholder="80" aria-label="<?= e(lang('cr_diastolic')) ?>"
                                           value="<?= e((string) ($bp['dia'] ?? '')) ?>">
                                </div>
                                <div class="form-text">mmHg</div>
                            <?php elseif ($field['type'] === 'bmi'): ?>
                                <label class="form-label" for="<?= $id ?>"><?= lang('cr_field_' . $name) ?></label>
                                <input id="<?= $id ?>" type="text" class="form-control" readonly tabindex="-1"
                                       data-bmi-weight="<?= e($field['weight_field']) ?>" data-bmi-height="<?= e($field['height_field']) ?>"
                                       value="<?= e($value) ?>" placeholder="<?= e(lang('cr_bmi_auto')) ?>">
                                <div class="form-text" data-bmi-class><?= $value !== '' ? e(bmi_class((float) $value)) : '' ?></div>
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
                                           min="0" max="999" class="form-control"
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
            </div>
        <?php endforeach; ?>

        <script>
            // IMC calculado na hora a partir do peso e da altura (o servidor recalcula ao salvar).
            (() => {
                const output = document.querySelector('[data-bmi-weight]');

                if (!output) {
                    return;
                }

                const weight = document.querySelector('[data-measure="' + output.dataset.bmiWeight + '"]');
                const height = document.querySelector('[data-measure="' + output.dataset.bmiHeight + '"]');
                const label = output.parentElement.querySelector('[data-bmi-class]');
                const classes = <?= json_encode(bmi_classes(), JSON_UNESCAPED_UNICODE) ?>;

                const update = () => {
                    const w = parseFloat(String(weight.value).replace(',', '.'));
                    const h = parseFloat(String(height.value).replace(',', '.'));

                    if (w > 0 && h > 0) {
                        const bmi = Math.round((w / (h * h)) * 10) / 10;
                        output.value = bmi;
                        label.textContent = (classes.find((item) => bmi < item[0]) || classes[classes.length - 1])[1];
                    } else {
                        output.value = '';
                        label.textContent = '';
                    }
                };

                weight.addEventListener('input', update);
                height.addEventListener('input', update);
            })();
        </script>

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
