<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regras dos turnos de atendimento.
 *
 * Quando um profissional tem turnos cadastrados, eles mandam na disponibilidade:
 *  - o dia de trabalho (início, fim e pausas) é montado a partir dos turnos que valem na data;
 *  - os horários oferecidos ao paciente seguem a grade de cada turno: início do turno + N × duração, recomeçando
 *    depois do intervalo, desde que o atendimento inteiro (duração do serviço) caiba num período livre.
 *
 * Sem turnos cadastrados, o sistema continua usando o horário semanal original (working_plan).
 * As exceções de horário de uma data específica continuam tendo prioridade sobre os turnos.
 */
class Shifts
{
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('provider_shifts_model');
    }

    public function provider_uses_shifts(int $provider_id): bool
    {
        return $this->CI->provider_shifts_model->provider_has_shifts($provider_id);
    }

    /**
     * Plano do dia no formato do working_plan ({start, end, breaks}) a partir dos turnos que valem na data, ou null
     * se o profissional não atende nesse dia.
     */
    public function date_plan(int $provider_id, string $date): ?array
    {
        return $this->combine($this->CI->provider_shifts_model->get_for_date($provider_id, $date));
    }

    /**
     * Junta vários turnos de um mesmo dia num plano {start, end, breaks}: o começo do primeiro, o fim do último, e
     * como pausas os intervalos de cada turno e os buracos entre um turno e outro.
     */
    public function combine(array $shifts): ?array
    {
        if (!$shifts) {
            return null;
        }

        $segments = [];

        foreach ($shifts as $shift) {
            foreach ($this->segments($shift) as $segment) {
                $segments[] = $segment;
            }
        }

        usort($segments, fn($a, $b) => strcmp($a[0], $b[0]));

        // Une trechos que se sobrepõem ou se encostam.
        $merged = [];

        foreach ($segments as [$start, $end]) {
            $last = count($merged) - 1;

            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        $breaks = [];

        for ($i = 1; $i < count($merged); $i++) {
            $breaks[] = ['start' => $merged[$i - 1][1], 'end' => $merged[$i][0]];
        }

        return [
            'start' => $merged[0][0],
            'end' => $merged[count($merged) - 1][1],
            'breaks' => $breaks,
        ];
    }

    /**
     * Horários oferecidos ao paciente numa data, seguindo a grade dos turnos.
     *
     * @param string $date Data (Y-m-d).
     * @param array $service Serviço escolhido (usa a duração do atendimento).
     * @param array $shifts Turnos que valem na data.
     * @param array $empty_periods Períodos livres do dia (já sem pausas, consultas e bloqueios).
     */
    public function generate_hours(string $date, array $service, array $shifts, array $empty_periods): array
    {
        $service_duration = max(1, (int) $service['duration']);

        $free = array_map(
            fn($period) => [
                strtotime($date . ' ' . $period['start']),
                strtotime($date . ' ' . $period['end']),
            ],
            $empty_periods,
        );

        $hours = [];

        foreach ($shifts as $shift) {
            $step = max(1, (int) $shift['duration']) * 60;

            foreach ($this->segments($shift) as [$segment_start, $segment_end]) {
                $start = strtotime($date . ' ' . $segment_start);
                $end = strtotime($date . ' ' . $segment_end);

                for ($slot = $start; $slot + $service_duration * 60 <= $end; $slot += $step) {
                    $slot_end = $slot + $service_duration * 60;

                    foreach ($free as [$free_start, $free_end]) {
                        if ($slot >= $free_start && $slot_end <= $free_end) {
                            $hours[] = date('H:i', $slot);
                            break;
                        }
                    }
                }
            }
        }

        $hours = array_values(array_unique($hours));

        sort($hours);

        return $hours;
    }

    /**
     * Atualiza o horário semanal (working_plan) do profissional a partir dos turnos em vigor hoje. O working_plan
     * continua sendo usado pela agenda interna para sombrear as horas sem atendimento.
     */
    public function sync_working_plan(int $provider_id): void
    {
        $today = date('Y-m-d');

        $in_effect = array_filter(
            $this->CI->provider_shifts_model->get_by_provider($provider_id),
            fn($shift) => (empty($shift['valid_from']) || $shift['valid_from'] <= $today) &&
                (empty($shift['valid_until']) || $shift['valid_until'] >= $today),
        );

        $working_plan = [];

        foreach (Provider_shifts_model::WEEKDAYS as $index => $weekday) {
            $day_shifts = array_filter(
                $in_effect,
                fn($shift) => $shift['weekday'] === $weekday ||
                    $shift['weekday'] === 'all' ||
                    ($shift['weekday'] === 'weekdays' && $index < 5),
            );

            $working_plan[$weekday] = $this->combine(array_values($day_shifts));
        }

        $this->CI->db->update(
            'user_settings',
            ['working_plan' => json_encode($working_plan)],
            ['id_users' => $provider_id],
        );
    }

    /**
     * Trechos de atendimento de um turno: [início, fim], partido em dois quando há intervalo. Horários em HH:MM.
     */
    protected function segments(array $shift): array
    {
        $start = substr($shift['start_time'], 0, 5);
        $end = substr($shift['end_time'], 0, 5);

        if (empty($shift['break_start']) || empty($shift['break_end'])) {
            return [[$start, $end]];
        }

        return [[$start, substr($shift['break_start'], 0, 5)], [substr($shift['break_end'], 0, 5), $end]];
    }
}
