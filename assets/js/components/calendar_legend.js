/**
 * Legenda das cores da agenda (application/views/components/calendar_legend.php).
 *
 * As duas visões da agenda (calendar_default_view.js e calendar_table_view.js) disparam o evento
 * "calendar:appointments" com os atendimentos carregados. A legenda mostra então as cores que estão na tela, cada uma
 * com os serviços que a usam. Sem atendimentos no período, volta para as cores dos serviços (montadas no servidor).
 */
App.Components.CalendarLegend = (function () {
    const DEFAULT_COLOR = '#7cbae8';

    let $appointments = null;
    let servicesHtml = '';

    /**
     * Um item da legenda.
     *
     * @param {String} color
     * @param {String} label
     *
     * @return {jQuery}
     */
    function createItem(color, label) {
        return $('<span/>', {class: 'calendar-legend-item'}).append(
            $('<span/>', {class: 'calendar-legend-swatch', 'aria-hidden': 'true'}).css('background-color', color),
            document.createTextNode(label),
        );
    }

    /**
     * Refaz a parte dos atendimentos com as cores que estão na tela.
     *
     * @param {Array} appointments
     */
    function render(appointments) {
        if (!$appointments || !$appointments.length) {
            return;
        }

        if (!Array.isArray(appointments) || !appointments.length) {
            $appointments.html(servicesHtml);
            return;
        }

        const groups = new Map(); // cor → nomes dos serviços

        appointments.forEach((appointment) => {
            const color = String(appointment.color || DEFAULT_COLOR).toLowerCase();
            const name = appointment.service?.name || '';

            if (!groups.has(color)) {
                groups.set(color, new Set());
            }

            if (name) {
                groups.get(color).add(name);
            }
        });

        const items = [...groups.entries()]
            .map(([color, names]) => ({color, label: [...names].sort().join(' / ')}))
            .sort((a, b) => a.label.localeCompare(b.label));

        $appointments.empty().append(items.map((item) => createItem(item.color, item.label)));
    }

    function initialize() {
        $appointments = $('#calendar-legend-appointments');
        servicesHtml = $appointments.html();

        document.addEventListener('calendar:appointments', (event) => render(event.detail));
    }

    // Registra antes dos módulos da agenda (que também iniciam no DOMContentLoaded), para não perder a primeira carga.
    document.addEventListener('DOMContentLoaded', initialize);

    return {
        render,
    };
})();
