/**
 * Cores dos atendimentos na agenda.
 *
 * O fundo do atendimento é a cor da situação dele (Configurações → Agendamento → Situações dos atendimentos; as cores
 * chegam em vars('appointment_status_colors')). A faixa da esquerda é a cor do atendimento, que vem do serviço. Uma
 * situação sem cor (ou um atendimento sem situação) fica com a cor do serviço, como antes.
 *
 * Usado pelas duas visões da agenda (calendar_default_view.js e calendar_table_view.js).
 */
App.Utils.AppointmentStatus = (function () {
    const DEFAULT_COLOR = '#7cbae8';

    /**
     * Cor da situação, ou null se a situação não tiver cor.
     *
     * @param {String} status
     *
     * @return {String|null}
     */
    function getColor(status) {
        const colors = vars('appointment_status_colors') || {};

        return (status && colors[status]) || null;
    }

    /**
     * Propriedades de cor de um evento do FullCalendar para um atendimento.
     *
     * @param {Object} appointment
     *
     * @return {Object}
     */
    function eventColors(appointment) {
        const serviceColor = appointment.color || DEFAULT_COLOR;

        return {
            backgroundColor: getColor(appointment.status) || serviceColor,
            borderColor: serviceColor,
            classNames: ['fc-appointment'],
        };
    }

    /**
     * Encaixe confirmado (o aviso de horário ocupado → "Continuar"): a consulta que estava na primeira situação da lista
     * (Marcado) passa para a situação de encaixe (Encaixado), se ela existir (application/config/appointment_statuses.php,
     * "overbooked").
     *
     * @param {Object} appointment Dados que vão ser salvos.
     */
    function markAsOverbooked(appointment) {
        const overbooked = vars('appointment_status_overbooked');

        const firstStatus = Object.keys(vars('appointment_status_colors') || {})[0];

        if (overbooked && (!appointment.status || appointment.status === firstStatus)) {
            appointment.status = overbooked;
        }
    }

    return {
        getColor,
        eventColors,
        markAsOverbooked,
    };
})();
