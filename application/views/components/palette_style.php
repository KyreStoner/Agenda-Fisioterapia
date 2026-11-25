<?php
/**
 * Aplica a paleta de cores sobre o tema (veja application/helpers/palette_helper.php).
 *
 * A tag sai sempre, mesmo vazia (verde original), porque as telas de escolha trocam o conteúdo dela para mostrar a
 * prévia da paleta antes de salvar.
 *
 * Local variables.
 *
 * @var array $palette Paleta já resolvida (active_color_palette() no painel, system_color_palette() nas públicas).
 */
?>
<style id="palette-style"><?= color_palette_css($palette) ?></style>
