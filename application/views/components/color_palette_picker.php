<?php
/**
 * Escolha da paleta de cores: um cartão por paleta, com as cores dela.
 *
 * O valor escolhido vai para um campo escondido (id = $input_id), que é o que a página salva. Os cartões são botões de
 * opção (radio), então também funcionam pelo teclado. No celular ficam 2 por linha.
 *
 * Local variables.
 *
 * @var string $input_id Id do campo escondido com o valor escolhido.
 * @var string|null $data_field Valor do data-field do campo escondido (Configurações → Geral usa "color_palette").
 * @var array $palettes Paletas (color_palette_options()).
 * @var array|null $clinic_palette Se vier, mostra antes a opção "Padrão da clínica" (valor "") com as cores dela.
 * @var array|null $custom_palette Se vier, mostra no fim a opção "Personalizada" (valor "custom") com estas cores.
 * @var string $label_id Id do texto que dá nome ao grupo (para leitores de tela).
 */

$options = [];

if (!empty($clinic_palette)) {
    $options[] = [
        'id' => '',
        'name' => lang('color_palette_follow_clinic'),
        'detail' => $clinic_palette['name'] ?? '',
        'swatches' => $clinic_palette['swatches'] ?? [],
    ];
}

foreach ($palettes as $palette) {
    $options[] = [
        'id' => $palette['id'],
        'name' => $palette['name'],
        'detail' => '',
        'swatches' => $palette['swatches'],
    ];
}

if (!empty($custom_palette)) {
    $options[] = [
        'id' => COLOR_PALETTE_CUSTOM,
        'name' => lang('color_palette_custom'),
        'detail' => '',
        'swatches' => $custom_palette['swatches'] ?? [],
    ];
}
?>

<input type="hidden" id="<?= e($input_id) ?>"<?= !empty($data_field) ? ' data-field="' . e($data_field) . '"' : '' ?>>

<div class="color-palette-picker row g-2" role="radiogroup" aria-labelledby="<?= e($label_id) ?>"
     data-input="#<?= e($input_id) ?>">
    <?php foreach ($options as $option): ?>
        <?php $radio_id = $input_id . '-option-' . ($option['id'] === '' ? 'clinic' : $option['id']); ?>
        <div class="col-6 col-md-4">
            <input type="radio" class="btn-check color-palette-option" name="<?= e($input_id) ?>-option"
                   id="<?= e($radio_id) ?>" value="<?= e($option['id']) ?>" autocomplete="off">
            <label class="color-palette-card" for="<?= e($radio_id) ?>">
                <span class="color-palette-swatches" aria-hidden="true">
                    <?php foreach ($option['swatches'] as $swatch): ?>
                        <span style="background-color: <?= e(palette_hex($swatch)) ?>"></span>
                    <?php endforeach; ?>
                </span>
                <span class="color-palette-name">
                    <span class="text-break">
                        <?= e($option['name']) ?>
                        <?php if ($option['detail'] !== ''): ?>
                            <small class="d-block text-muted"><?= e($option['detail']) ?></small>
                        <?php endif; ?>
                    </span>
                    <i class="fas fa-check-circle color-palette-check" title="<?= e(lang('color_palette_selected')) ?>"></i>
                </span>
            </label>
        </div>
    <?php endforeach; ?>
</div>
