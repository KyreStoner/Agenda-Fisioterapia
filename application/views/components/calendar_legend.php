<?php
/**
 * Legenda das cores da agenda, discreta, numa linha em cima do rodapé (sempre visível, só na tela da agenda).
 *
 * O fundo de cada atendimento é a cor da situação dele (Configurações → Agendamento → Situações dos atendimentos) e a
 * faixa da esquerda é a cor do serviço (assets/js/utils/appointment_status.js). As outras cores são fixas e precisam
 * acompanhar EVENT_COLORS em assets/js/utils/calendar_default_view.js e calendar_table_view.js.
 *
 * No celular a linha rola para o lado dentro dela mesma, sem rolar a página.
 *
 * Local variables.
 *
 * @var array $statuses Situações com cor (appointment_status_items()).
 */

$fixed_items = [
    ['label' => lang('calendar_legend_service_stripe'), 'class' => 'calendar-legend-stripe'],
    ['label' => lang('unavailability'), 'style' => 'background-color: #879DB4'],
    ['label' => lang('blocked_period'), 'style' => 'background-color: #d65069'],
    ['label' => lang('calendar_legend_closed'), 'class' => 'calendar-legend-closed'],
];
?>

<div id="calendar-legend" class="d-flex align-items-center px-2 py-1 border-bottom"
     aria-label="<?= e(lang('calendar_legend')) ?>">
    <span class="calendar-legend-title"><?= lang('calendar_legend') ?>:</span>

    <?php foreach ($statuses as $status): ?>
        <span class="calendar-legend-item" title="<?= e($status['hint']) ?>">
            <span class="calendar-legend-swatch" style="background-color: <?= e($status['color']) ?>"
                  aria-hidden="true"></span>
            <?= e($status['name']) ?>
        </span>
    <?php endforeach; ?>

    <span class="calendar-legend-separator" aria-hidden="true"></span>

    <?php foreach ($fixed_items as $item): ?>
        <span class="calendar-legend-item">
            <span class="calendar-legend-swatch <?= e($item['class'] ?? '') ?>"
                  style="<?= e($item['style'] ?? '') ?>" aria-hidden="true"></span>
            <?= e($item['label']) ?>
        </span>
    <?php endforeach; ?>
</div>
