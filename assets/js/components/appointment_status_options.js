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
 * Appointment status options component.
 *
 * This module implements the appointment status options.
 */
App.Components.AppointmentStatusOptions = (function () {
    const DEFAULT_COLOR = '#7cbae8';

    /**
     * Render an appointment status option.
     *
     * Cada situação tem nome e cor (a cor do fundo do atendimento na agenda). Montado com jQuery para o nome nunca ser
     * interpretado como HTML.
     *
     * @param {String|Object} [appointmentStatusOption] Nome, ou {name, color}.
     *
     * @return {jQuery} Returns a jQuery selector with the list group item markup.
     */
    function renderListGroupItem(appointmentStatusOption = '') {
        const option =
            typeof appointmentStatusOption === 'object' && appointmentStatusOption !== null
                ? appointmentStatusOption
                : {name: appointmentStatusOption, color: DEFAULT_COLOR};

        return $('<li/>', {
            class: 'list-group-item d-flex justify-content-between align-items-center gap-2 p-0 border-0 mb-3 appointment-status-option',
            html: [
                $('<input/>', {
                    type: 'color',
                    class: 'form-control form-control-color flex-shrink-0 appointment-status-option-color',
                    value: option.color || DEFAULT_COLOR,
                    title: lang('color'),
                    'aria-label': lang('color'),
                }),
                $('<label/>', {
                    class: 'flex-grow-1',
                    html: $('<input/>', {
                        type: 'text',
                        class: 'form-control appointment-status-option-name',
                        value: option.name || '',
                    }),
                }),
                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-outline-danger delete-appointment-status-option',
                    html: $('<i/>', {class: 'fas fa-trash'}),
                }),
            ],
        });
    }

    /**
     * Event: Delete Appointment Status Option "Click"
     *
     * @param {jQuery.Event} event
     */
    function onDeleteAppointmentStatusOptionClick(event) {
        $(event.currentTarget).closest('li').remove();
    }

    /**
     * Event: Add Appointment Status Option "Click"
     *
     * @param {jQuery.Event} event
     */
    function onAddAppointmentStatusOptionClick(event) {
        const $target = $(event.currentTarget);

        const $listGroup = $target.closest('.appointment-status-options').find('.list-group');

        if (!$listGroup.length) {
            return;
        }

        renderListGroupItem().appendTo($listGroup);
    }

    /**
     * Get target options.
     *
     * @param {jQuery} $target Container element ".status-list" selector.
     *
     * @return {String[]}
     */
    function getOptions($target) {
        const $listGroup = $target.find('.list-group');

        const appointmentStatusOptions = [];

        $listGroup.find('li').each((index, listGroupItemEl) => {
            const $listGroupItem = $(listGroupItemEl);
            const appointmentStatusOption = $listGroupItem.find('.appointment-status-option-name').val().trim();

            if (appointmentStatusOption) {
                appointmentStatusOptions.push(appointmentStatusOption);
            }
        });

        return appointmentStatusOptions;
    }

    /**
     * Cor de cada situação.
     *
     * @param {jQuery} $target Container element ".appointment-status-options" selector.
     *
     * @return {Object} Nome → cor.
     */
    function getColors($target) {
        const colors = {};

        $target.find('.list-group li').each((index, listGroupItemEl) => {
            const $listGroupItem = $(listGroupItemEl);
            const name = $listGroupItem.find('.appointment-status-option-name').val().trim();

            if (name) {
                colors[name] = $listGroupItem.find('.appointment-status-option-color').val();
            }
        });

        return colors;
    }

    /**
     * Set target options.
     *
     * @param {jQuery} $target Container element ".status-list" selector.
     * @param {Array} appointmentStatusOptions Appointment status options (nomes, ou {name, color}).
     */
    function setOptions($target, appointmentStatusOptions) {
        if (!$target.length || !appointmentStatusOptions || !appointmentStatusOptions.length) {
            return;
        }

        const $listGroup = $target.find('.list-group');

        if (!$listGroup.length) {
            return;
        }

        $listGroup.empty();

        appointmentStatusOptions.forEach((appointmentStatusOption) => {
            renderListGroupItem(appointmentStatusOption).appendTo($listGroup);
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $(document).on('click', '.delete-appointment-status-option', onDeleteAppointmentStatusOptionClick);
        $(document).on('click', '.add-appointment-status-option', onAddAppointmentStatusOptionClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        getOptions,
        getColors,
        setOptions,
    };
})();
