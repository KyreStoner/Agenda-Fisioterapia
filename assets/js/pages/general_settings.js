/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * General settings page.
 *
 * This module implements the functionality of the general settings page.
 */
App.Pages.GeneralSettings = (function () {
    const $saveSettings = $('#save-settings');
    const $companyLogo = $('#company-logo');
    const $companyLogoPreview = $('#company-logo-preview');
    const $removeCompanyLogo = $('#remove-company-logo');
    const $companyColor = $('#company-color');
    const $companyColorGroup = $('#company-color-group');
    const $colorPalette = $('#color-palette');
    const $colorPalettePicker = $('#general-settings .color-palette-picker');
    let companyColorPreviewTimeout = null;
    let companyLogoBase64 = '';

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#general-settings .is-invalid').removeClass('is-invalid');

            // Validate required fields.

            let missingRequiredFields = false;

            $('#general-settings .required').each((index, requiredField) => {
                const $requiredField = $(requiredField);

                if (!$requiredField.val()) {
                    $requiredField.addClass('is-invalid');
                    missingRequiredFields = true;
                }
            });

            if (missingRequiredFields) {
                throw new Error(lang('fields_are_required'));
            }

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    function deserialize(generalSettings) {
        generalSettings.forEach((generalSetting) => {
            if (generalSetting.name === 'company_logo' && generalSetting.value) {
                companyLogoBase64 = generalSetting.value;
                $companyLogoPreview.attr('src', generalSetting.value);
                $companyLogoPreview.prop('hidden', false);
                $removeCompanyLogo.prop('hidden', false);
                return;
            }

            const $field = $('[data-field="' + generalSetting.name + '"]');

            $field.is(':checkbox')
                ? $field.prop('checked', Boolean(Number(generalSetting.value)))
                : $field.val(generalSetting.value);
        });

        // Paleta já conferida pelo servidor (uma paleta apagada do arquivo de configuração volta para o verde).
        $colorPalette.val(vars('color_palette'));
        App.Components.ColorPalettePicker.sync($colorPalettePicker);
        $companyColorGroup.prop('hidden', $colorPalette.val() !== 'custom');
    }

    function serialize() {
        const generalSettings = [];

        $('[data-field]').each((index, field) => {
            const $field = $(field);

            generalSettings.push({
                name: $field.data('field'),
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
            });
        });

        generalSettings.push({
            name: 'company_logo',
            value: companyLogoBase64,
        });

        return generalSettings;
    }

    /**
     * Save the account information.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            App.Layouts.Backend.displayNotification(lang('user_settings_are_invalid'));
            return;
        }

        const generalSettings = serialize();

        App.Http.GeneralSettings.save(generalSettings).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'), [
                {
                    label: lang('reload'), // Reload Page
                    function: () => window.location.reload(),
                },
            ]);
        });
    }

    /**
     * Convert the selected image to a base64 encoded string.
     */
    function onCompanyLogoChange() {
        const file = $companyLogo[0].files[0];

        if (!file) {
            $removeCompanyLogo.trigger('click');
            return;
        }

        App.Utils.File.toBase64(file).then((base64) => {
            companyLogoBase64 = base64;
            $companyLogoPreview.attr('src', base64);
            $companyLogoPreview.prop('hidden', false);
            $removeCompanyLogo.prop('hidden', false);
        });
    }

    /**
     * Remove the company logo data.
     */
    function onRemoveCompanyLogoClick() {
        companyLogoBase64 = '';
        $companyLogo.val('');
        $companyLogoPreview.attr('src', '#');
        $companyLogoPreview.prop('hidden', true);
        $removeCompanyLogo.prop('hidden', true);
    }

    /**
     * Paleta escolhida: mostra a prévia na página e, na "Personalizada", o campo da cor.
     */
    function onColorPaletteChange() {
        const palette = $colorPalette.val();

        const isCustom = palette === 'custom';

        // "#ffffff" é o valor de fábrica (= nenhuma cor): começa a cor personalizada pelo verde original.
        if (isCustom && (!$companyColor.val() || $companyColor.val().toLowerCase() === '#ffffff')) {
            $companyColor.val(vars('default_color'));
        }

        $companyColorGroup.prop('hidden', !isCustom);

        const request = App.Components.ColorPalettePicker.preview(palette, isCustom ? $companyColor.val() : '');

        if (isCustom) {
            request.done(updateCustomSwatches);
        }
    }

    /**
     * Mostra no cartão "Personalizada" os tons da cor escolhida.
     *
     * @param {Object} response Resposta de color_palettes/preview.
     */
    function updateCustomSwatches(response) {
        $('#color-palette-option-custom + .color-palette-card .color-palette-swatches > span').each((index, swatch) => {
            $(swatch).css('background-color', response.swatches[index] || '');
        });
    }

    /**
     * Cor personalizada mudou: atualiza a prévia (com uma pequena espera, porque o seletor de cor dispara muitas
     * vezes enquanto a pessoa arrasta).
     */
    function onCompanyColorInput() {
        if ($colorPalette.val() !== 'custom') {
            return;
        }

        clearTimeout(companyColorPreviewTimeout);

        companyColorPreviewTimeout = setTimeout(() => {
            App.Components.ColorPalettePicker.preview('custom', $companyColor.val()).done(updateCustomSwatches);
        }, 200);
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);

        $companyLogo.on('change', onCompanyLogoChange);

        $removeCompanyLogo.on('click', onRemoveCompanyLogoClick);

        $colorPalette.on('change', onColorPaletteChange);

        $companyColor.on('input change', onCompanyColorInput);

        const generalSettings = vars('general_settings');

        deserialize(generalSettings);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
