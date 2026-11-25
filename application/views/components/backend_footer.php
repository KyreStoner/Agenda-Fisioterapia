<?php
/**
 * Rodapé do painel: sempre visível. À esquerda o nome e a versão do sistema (com o crédito discreto da licença),
 * à direita quem está usando e o perfil.
 *
 * Uma página pode pôr uma linha a mais em cima do rodapé (fica sempre visível junto com ele) com
 * section('footer_top') ... end_section('footer_top'). A agenda usa para a legenda das cores.
 *
 * Local variables.
 *
 * @var string $user_display_name
 */
$role_slug = session('role_slug');
?>
<div id="footer" class="mt-auto bg-body border-top text-muted"
     style="font-size: 11px; position: sticky; bottom: 0; z-index: 1020;">
    <?php slot('footer_top'); ?>

    <div class="d-flex align-items-center gap-2 px-2 py-1">
        <div class="text-truncate">
            <span class="fw-semibold"><?= e(app_name()) ?></span> v<?= e(config('app_version')) ?>
            <span class="d-none d-md-inline">
                &middot; <?= lang('based_on') ?>
                <a href="https://easyappointments.org" target="_blank" class="text-muted">Easy!Appointments</a>
                &middot; <a href="https://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank" class="text-muted">GPL-3.0</a>
            </span>
        </div>

        <?php if (config('language_selection')): ?>
            <span id="select-language" class="badge bg-dark" role="button">
                <i class="fas fa-language me-1"></i><?= ucfirst(config('language')) ?>
            </span>
        <?php endif; ?>

        <div class="ms-auto text-truncate text-end">
            <i class="fas fa-user me-1"></i>
            <span id="footer-user-display-name" class="fw-semibold"><?= e($user_display_name) ?></span>
            <?php if ($role_slug): ?>
                / <span id="footer-user-role"><?= lang('role_' . $role_slug) ?: e($role_slug) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
