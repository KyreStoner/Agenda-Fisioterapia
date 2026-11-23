<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Turnos de atendimento dos profissionais.
 *
 * Um turno define em que dia(s) e horário o profissional atende, com intervalo opcional, a duração de cada horário da
 * grade de agendamento, a vigência e a frequência (toda semana ou semanas alternadas).
 */
class Provider_shifts_model extends EA_Model
{
    const WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /**
     * Valores aceitos em "weekday": um dia da semana, "weekdays" (segunda a sexta) ou "all" (todos os dias).
     */
    const WEEKDAY_OPTIONS = [...self::WEEKDAYS, 'weekdays', 'all'];

    const FREQUENCIES = ['weekly', 'biweekly'];

    const MIN_DURATION = 5;

    const MAX_DURATION = 480;

    protected array $casts = [
        'id' => 'integer',
        'id_users_provider' => 'integer',
        'duration' => 'integer',
    ];

    /**
     * Salva (cria ou atualiza) um turno e devolve o ID.
     *
     * @throws InvalidArgumentException Com a mensagem para o usuário quando algum campo é inválido.
     */
    public function save(array $shift): int
    {
        $shift = $this->normalize($shift);

        $this->validate($shift);

        $shift['update_datetime'] = date('Y-m-d H:i:s');

        if (empty($shift['id'])) {
            $shift['create_datetime'] = $shift['update_datetime'];

            $this->db->insert('provider_shifts', $shift);

            return (int) $this->db->insert_id();
        }

        $this->db->update('provider_shifts', $shift, ['id' => $shift['id']]);

        return (int) $shift['id'];
    }

    /**
     * Limpa os valores recebidos do formulário: campos vazios viram NULL e horários ficam no formato HH:MM.
     */
    public function normalize(array $shift): array
    {
        $shift = array_intersect_key(
            $shift,
            array_flip([
                'id',
                'id_users_provider',
                'weekday',
                'frequency',
                'start_time',
                'end_time',
                'break_start',
                'break_end',
                'duration',
                'valid_from',
                'valid_until',
                'notes',
            ]),
        );

        foreach (['break_start', 'break_end', 'valid_from', 'valid_until', 'notes'] as $field) {
            $value = trim((string) ($shift[$field] ?? ''));
            $shift[$field] = $value === '' ? null : $value;
        }

        foreach (['start_time', 'end_time', 'break_start', 'break_end'] as $field) {
            if (!empty($shift[$field])) {
                $shift[$field] = substr($shift[$field], 0, 5);
            }
        }

        $shift['frequency'] = $shift['frequency'] ?? 'weekly';

        if (empty($shift['id'])) {
            unset($shift['id']);
        }

        return $shift;
    }

    /**
     * Valida um turno já normalizado.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $shift): void
    {
        if (!empty($shift['id']) && !$this->db->get_where('provider_shifts', ['id' => $shift['id']])->num_rows()) {
            throw new InvalidArgumentException(lang('shift_not_found'));
        }

        if (empty($shift['id_users_provider'])) {
            throw new InvalidArgumentException(lang('shift_provider_required'));
        }

        if (!in_array($shift['weekday'] ?? '', self::WEEKDAY_OPTIONS, true)) {
            throw new InvalidArgumentException(lang('shift_weekday_required'));
        }

        if (!in_array($shift['frequency'], self::FREQUENCIES, true)) {
            throw new InvalidArgumentException(lang('shift_invalid_frequency'));
        }

        foreach (['start_time', 'end_time'] as $field) {
            if (!$this->is_time($shift[$field] ?? null)) {
                throw new InvalidArgumentException(lang('shift_times_required'));
            }
        }

        if ($shift['start_time'] >= $shift['end_time']) {
            throw new InvalidArgumentException(lang('shift_end_after_start'));
        }

        if (!empty($shift['break_start']) || !empty($shift['break_end'])) {
            if (!$this->is_time($shift['break_start']) || !$this->is_time($shift['break_end'])) {
                throw new InvalidArgumentException(lang('shift_break_incomplete'));
            }

            if (
                $shift['break_start'] >= $shift['break_end'] ||
                $shift['break_start'] <= $shift['start_time'] ||
                $shift['break_end'] >= $shift['end_time']
            ) {
                throw new InvalidArgumentException(lang('shift_break_inside'));
            }
        }

        $duration = filter_var($shift['duration'] ?? null, FILTER_VALIDATE_INT);

        if ($duration === false || $duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
            throw new InvalidArgumentException(lang('shift_invalid_duration'));
        }

        foreach (['valid_from', 'valid_until'] as $field) {
            if (!empty($shift[$field]) && !$this->is_date($shift[$field])) {
                throw new InvalidArgumentException(lang('shift_invalid_date'));
            }
        }

        if (!empty($shift['valid_from']) && !empty($shift['valid_until']) && $shift['valid_from'] > $shift['valid_until']) {
            throw new InvalidArgumentException(lang('shift_validity_order'));
        }

        if ($shift['frequency'] === 'biweekly' && empty($shift['valid_from'])) {
            throw new InvalidArgumentException(lang('shift_biweekly_needs_start'));
        }
    }

    public function delete(int $shift_id): void
    {
        $this->db->delete('provider_shifts', ['id' => $shift_id]);
    }

    public function find(int $shift_id): array
    {
        $shift = $this->db->get_where('provider_shifts', ['id' => $shift_id])->row_array();

        if (!$shift) {
            throw new InvalidArgumentException(lang('shift_not_found'));
        }

        $this->cast($shift);

        return $shift;
    }

    /**
     * Todos os turnos de um profissional, ordenados por dia da semana e horário.
     */
    public function get_by_provider(int $provider_id): array
    {
        $shifts = $this->db
            ->order_by('start_time')
            ->get_where('provider_shifts', ['id_users_provider' => $provider_id])
            ->result_array();

        foreach ($shifts as &$shift) {
            $this->cast($shift);
        }

        // "Dias úteis" e "Todos os dias" primeiro, depois de segunda a domingo.
        $order = array_flip(['weekdays', 'all', ...self::WEEKDAYS]);

        usort(
            $shifts,
            fn($a, $b) => [$order[$a['weekday']] ?? 99, $a['start_time']] <=> [$order[$b['weekday']] ?? 99, $b['start_time']],
        );

        return $shifts;
    }

    public function provider_has_shifts(int $provider_id): bool
    {
        return $this->db->get_where('provider_shifts', ['id_users_provider' => $provider_id], 1)->num_rows() > 0;
    }

    /**
     * Turnos que valem numa data: dia da semana, vigência e frequência.
     */
    public function get_for_date(int $provider_id, string $date): array
    {
        return array_values(
            array_filter($this->get_by_provider($provider_id), fn($shift) => $this->applies_on($shift, $date)),
        );
    }

    /**
     * Diz se o turno vale na data informada (Y-m-d).
     */
    public function applies_on(array $shift, string $date): bool
    {
        $timestamp = strtotime($date);

        if (!$this->matches_weekday($shift, $timestamp)) {
            return false;
        }

        if (!empty($shift['valid_from']) && $date < $shift['valid_from']) {
            return false;
        }

        if (!empty($shift['valid_until']) && $date > $shift['valid_until']) {
            return false;
        }

        if ($shift['frequency'] === 'biweekly' && !empty($shift['valid_from'])) {
            // A semana do primeiro atendimento (primeiro dia válido a partir da vigência inicial) é a semana 0; o
            // turno vale nas semanas pares (0, 2, 4...).
            $first_day = strtotime($shift['valid_from']);

            for ($i = 0; $i < 7 && !$this->matches_weekday($shift, $first_day); $i++) {
                $first_day = strtotime('+1 day', $first_day);
            }

            $first_monday = strtotime('monday this week', $first_day);
            $date_monday = strtotime('monday this week', $timestamp);
            $weeks = (int) round(($date_monday - $first_monday) / (7 * 86400));

            return $weeks % 2 === 0;
        }

        return true;
    }

    private function matches_weekday(array $shift, int $timestamp): bool
    {
        return match ($shift['weekday']) {
            'all' => true,
            'weekdays' => (int) date('N', $timestamp) <= 5,
            default => $shift['weekday'] === strtolower(date('l', $timestamp)),
        };
    }

    private function is_time(?string $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    private function is_date(?string $value): bool
    {
        $date = is_string($value) ? DateTime::createFromFormat('Y-m-d', $value) : false;

        return $date && $date->format('Y-m-d') === $value;
    }
}
