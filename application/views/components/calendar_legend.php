<?php
/**
 * Legenda das cores da agenda, discreta, numa linha em cima do rodapé (sempre visível).
 *
 * Cada atendimento guarda a própria cor: ela vem do serviço (Serviços → cor) e pode ser trocada na janela do
 * atendimento. Por isso a parte dos atendimentos é refeita por assets/js/components/calendar_legend.js com as cores dos
 * atendimentos que estão na tela (agrupadas: cor → serviços). Enquanto a agenda carrega, ou se não houver atendimentos
 * no período, aparecem as cores dos serviços.
 *
 * As outras cores são fixas e precisam acompanhar EVENT_COLORS em assets/js/utils/calendar_default_view.js e
 * calendar_table_view.js. No celular a linha rola para o lado dentro dela mesma, sem rolar a página.
 *
 * Local variables.
 *
 * @var array $services Serviços que aparecem na agenda (com "name" e "color").
 */

// Serviços agrupados pela cor (dois serviços com a mesma cor saem juntos: "A / B").
$service_colors = [];

foreach ($services as $service) {
    $color = palette_hex((string) ($service['color'] ?? '#7cbae8'));
    $service_colors[$color][] = $service['name'];
}

$fixed_items = [
    ['label' => lang('unavailability'), 'style' => 'background-color: #879DB4'],
    ['label' => lang('blocked_period'), 'style' => 'background-color: #d65069'],
    ['label' => lang('calendar_legend_closed'), 'class' => 'calendar-legend-closed'],
];
?>

<div id="calendar-legend" class="d-flex align-items-center px-2 py-1 border-bottom"
     aria-label="<?= e(lang('calendar_legend')) ?>">
    <span class="calendar-legend-title me-1"><?= lang('calendar_legend') ?>:</span>

    <span id="calendar-legend-appointments" class="d-inline-flex align-items-center">
        <?php foreach ($service_colors as $color => $names): ?>
            <span class="calendar-legend-item">
                <span class="calendar-legend-swatch" style="background-color: <?= e($color) ?>"
                      aria-hidden="true"></span>
                <?= e(implode(' / ', $names)) ?>
            </span>
        <?php endforeach; ?>
    </span>

    <?php foreach ($fixed_items as $item): ?>
        <span class="calendar-legend-item">
            <span class="calendar-legend-swatch <?= e($item['class'] ?? '') ?>"
                  style="<?= e($item['style'] ?? '') ?>" aria-hidden="true"></span>
            <?= e($item['label']) ?>
        </span>
    <?php endforeach; ?>
</div>
