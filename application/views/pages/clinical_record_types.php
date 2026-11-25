<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * Configurações → Prontuário: tipos de registro e permissões por perfil (ver / adicionar).
 *
 * Variáveis (html_vars): types, record_counts, forms, roles, can_edit, success_message, error_message.
 */
$csrf_hash = get_instance()->security->get_csrf_hash();
$can_edit = vars('can_edit');
$disabled = $can_edit ? '' : 'disabled';
$counts = vars('record_counts');
?>

<div id="clinical-record-types-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <div class="d-flex justify-content-between align-items-center gap-2 border-bottom mb-3 py-2">
                <h4 class="mb-0 fw-light"><?= lang('cr_types_title') ?></h4>

                <?php if ($can_edit && vars('types')): ?>
                    <button type="submit" form="clinical-record-types-form" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i><?= lang('save') ?>
                    </button>
                <?php endif; ?>
            </div>

            <p class="text-muted small"><?= lang('cr_types_hint') ?></p>

            <?php if (vars('success_message')): ?>
                <div class="alert alert-success" role="status"><?= e(vars('success_message')) ?></div>
            <?php endif; ?>

            <?php if (vars('error_message')): ?>
                <div class="alert alert-danger" role="alert"><?= e(vars('error_message')) ?></div>
            <?php endif; ?>

            <form id="clinical-record-types-form" method="post" action="<?= site_url('clinical_record_types/save') ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">

                <?php foreach (vars('types') as $type): ?>
                    <?php
                    $field = fn(string $name) => 'types[' . $type['id'] . '][' . $name . ']';
                    $id = fn(string $name) => 'cr-type-' . $type['id'] . '-' . $name;
                    $total = $counts[$type['id']] ?? 0;
                    ?>
                    <div class="card mb-3 <?= $type['active'] ? '' : 'opacity-75' ?>">
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-6 col-xl-5">
                                    <label class="form-label small mb-1" for="<?= $id('name') ?>"><?= lang('cr_type_name') ?></label>
                                    <input id="<?= $id('name') ?>" name="<?= $field('name') ?>" class="form-control"
                                           value="<?= e($type['name']) ?>" maxlength="100" required <?= $disabled ?>>
                                </div>
                                <div class="col-8 col-md-6 col-xl-3">
                                    <label class="form-label small mb-1" for="<?= $id('form') ?>"><?= lang('cr_type_form') ?></label>
                                    <select id="<?= $id('form') ?>" name="<?= $field('form') ?>" class="form-select" <?= $disabled ?>>
                                        <?php foreach (vars('forms') as $form): ?>
                                            <option value="<?= e($form) ?>" <?= $form === $type['form'] ? 'selected' : '' ?>>
                                                <?= lang('cr_type_form_' . $form) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4 col-md-3 col-xl-2">
                                    <label class="form-label small mb-1" for="<?= $id('order') ?>"><?= lang('cr_type_order') ?></label>
                                    <input id="<?= $id('order') ?>" name="<?= $field('sort_order') ?>" type="number"
                                           class="form-control" value="<?= (int) $type['sort_order'] ?>" <?= $disabled ?>>
                                </div>
                                <div class="col-12 col-md-3 col-xl-2 d-flex align-items-md-end">
                                    <div class="form-check form-switch mb-md-2">
                                        <input id="<?= $id('active') ?>" name="<?= $field('active') ?>" value="1" type="checkbox"
                                               class="form-check-input" <?= $type['active'] ? 'checked' : '' ?> <?= $disabled ?>>
                                        <label class="form-check-label" for="<?= $id('active') ?>"><?= lang('cr_type_active') ?></label>
                                    </div>
                                </div>
                                <?php if (!$type['active']): ?>
                                    <div class="col-12 small text-muted"><?= lang('cr_type_inactive_hint') ?></div>
                                <?php endif; ?>
                            </div>

                            <table class="table table-sm align-middle mb-2 clinical-record-type-permissions">
                                <thead>
                                <tr>
                                    <th scope="col"><?= lang('cr_type_profile') ?></th>
                                    <th scope="col" class="text-center" style="width: 6rem;"><?= lang('cr_type_view') ?></th>
                                    <th scope="col" class="text-center" style="width: 6rem;"><?= lang('cr_type_add') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach (vars('roles') as $role): ?>
                                    <?php $permission = $type['permissions'][$role]; ?>
                                    <tr>
                                        <th scope="row" class="fw-normal" style="text-transform: none;"><?= lang('role_' . $role) ?></th>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input permission-view" value="1"
                                                   name="<?= $field('permissions') ?>[<?= e($role) ?>][view]"
                                                   aria-label="<?= e(lang('cr_type_view') . ' · ' . lang('role_' . $role)) ?>"
                                                <?= $permission['view'] ? 'checked' : '' ?> <?= $disabled ?>>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input permission-add" value="1"
                                                   name="<?= $field('permissions') ?>[<?= e($role) ?>][add]"
                                                   aria-label="<?= e(lang('cr_type_add') . ' · ' . lang('role_' . $role)) ?>"
                                                <?= $permission['add'] ? 'checked' : '' ?> <?= $disabled ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <span><?= $total ?> <?= lang('cr_type_records') ?></span>
                                <?php if ($can_edit && !$total): ?>
                                    <button type="submit" form="delete-type-<?= (int) $type['id'] ?>"
                                            class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i><?= lang('delete') ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>

            <?php // Formulários de exclusão fora do formulário principal (formulário dentro de formulário não vale). ?>
            <?php foreach (vars('types') as $type): ?>
                <?php if ($can_edit && empty($counts[$type['id']])): ?>
                    <form id="delete-type-<?= (int) $type['id'] ?>" method="post" class="d-none"
                          action="<?= site_url('clinical_record_types/delete') ?>"
                          onsubmit="return confirm(<?= e(json_encode(lang('cr_type_delete_confirm'))) ?>)">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
                        <input type="hidden" name="id" value="<?= (int) $type['id'] ?>">
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($can_edit): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="fw-light mb-3"><?= lang('cr_type_new') ?></h5>
                        <form method="post" action="<?= site_url('clinical_record_types/create') ?>" class="row g-2 align-items-end">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_hash) ?>">
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1" for="new-type-name"><?= lang('cr_type_name') ?></label>
                                <input id="new-type-name" name="name" class="form-control" maxlength="100" required
                                       placeholder="Reavaliação">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small mb-1" for="new-type-form"><?= lang('cr_type_form') ?></label>
                                <select id="new-type-form" name="form" class="form-select">
                                    <?php foreach (vars('forms') as $form): ?>
                                        <option value="<?= e($form) ?>"><?= lang('cr_type_form_' . $form) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-plus-square me-2"></i><?= lang('cr_type_add_button') ?>
                                </button>
                            </div>
                            <div class="col-12 small text-muted"><?= lang('cr_type_new_hint') ?></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
    // Quem adiciona também vê: marcar "Adicionar" marca "Ver"; desmarcar "Ver" desmarca "Adicionar".
    document.querySelectorAll('.clinical-record-type-permissions tbody tr').forEach((row) => {
        const view = row.querySelector('.permission-view');
        const add = row.querySelector('.permission-add');

        add.addEventListener('change', () => {
            if (add.checked) {
                view.checked = true;
            }
        });

        view.addEventListener('change', () => {
            if (!view.checked) {
                add.checked = false;
            }
        });
    });
</script>

<?php end_section('scripts'); ?>
