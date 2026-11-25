/**
 * Escolha da paleta de cores (application/views/components/color_palette_picker.php).
 *
 * Liga os cartões ao campo escondido que a página salva e mostra a prévia da paleta na própria página, antes de
 * salvar (o CSS vem de color_palettes/preview e substitui o conteúdo de <style id="palette-style">).
 */
App.Components.ColorPalettePicker = (function () {
    let lastPreview = 0;

    /**
     * Campo escondido de um seletor.
     *
     * @param {jQuery} $picker Elemento ".color-palette-picker".
     *
     * @return {jQuery}
     */
    function getInput($picker) {
        return $($picker.data('input'));
    }

    /**
     * Marca o cartão que corresponde ao valor do campo escondido.
     *
     * @param {jQuery} $picker
     */
    function sync($picker) {
        const value = getInput($picker).val() || '';

        $picker.find('.color-palette-option').each((index, option) => {
            option.checked = option.value === value;
        });
    }

    /**
     * Mostra uma paleta na página atual, sem salvar.
     *
     * @param {String} palette Id da paleta, "custom" ou "" (paleta da clínica).
     * @param {String} [color] Cor da paleta "custom" (#rrggbb).
     *
     * @return {jQuery.jqXHR}
     */
    function preview(palette, color) {
        const request = ++lastPreview;

        const url = App.Utils.Url.siteUrl('color_palettes/preview');

        return $.getJSON(url, {palette, color: color || ''}).done((response) => {
            if (request !== lastPreview) {
                return; // Chegou depois de uma escolha mais nova.
            }

            $('#palette-style').text(response.css);
            $('meta[name="theme-color"]').attr('content', response.theme_color);
        });
    }

    /**
     * Cartão clicado: passa o valor para o campo escondido e avisa a página (evento "change" do campo).
     *
     * @param {jQuery.Event} event
     */
    function onOptionChange(event) {
        const $option = $(event.currentTarget);

        const $picker = $option.closest('.color-palette-picker');

        getInput($picker).val($option.val()).trigger('change');
    }

    function initialize() {
        $(document).on('change', '.color-palette-option', onOptionChange);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        sync,
        preview,
    };
})();
