<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: turnos de atendimento por profissional.
 *
 * Cada turno diz em que dia(s) e horário o profissional atende, com intervalo opcional, a duração de cada horário
 * da grade, a vigência (data inicial e final) e a frequência (toda semana ou semanas alternadas). Quando o
 * profissional tem turnos, a disponibilidade do agendamento passa a ser calculada por eles (veja a library Shifts).
 *
 * Para ninguém perder a configuração atual, o horário semanal de cada profissional é convertido em turnos.
 */
class Migration_Create_provider_shifts_table extends EA_Migration
{
    /**
     * Duração padrão (minutos) dos turnos criados a partir do horário semanal antigo.
     */
    const DEFAULT_DURATION = 60;

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('provider_shifts')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'id_users_provider' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'weekday' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => false],
                'frequency' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => false, 'default' => 'weekly'],
                'start_time' => ['type' => 'TIME', 'null' => false],
                'end_time' => ['type' => 'TIME', 'null' => false],
                'break_start' => ['type' => 'TIME', 'null' => true],
                'break_end' => ['type' => 'TIME', 'null' => true],
                'duration' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 60],
                'valid_from' => ['type' => 'DATE', 'null' => true],
                'valid_until' => ['type' => 'DATE', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'create_datetime' => ['type' => 'DATETIME', 'null' => true],
                'update_datetime' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_provider');
            $this->dbforge->create_table('provider_shifts', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE `' .
                    $this->db->dbprefix('provider_shifts') .
                    '` ADD CONSTRAINT `provider_shifts_users_provider` FOREIGN KEY (`id_users_provider`) REFERENCES `' .
                    $this->db->dbprefix('users') .
                    '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE',
            );
        }

        $this->convert_working_plans();
    }

    /**
     * Converte o horário semanal (working_plan) de cada profissional em turnos.
     *
     * Um dia com uma pausa vira um turno com intervalo. Um dia com várias pausas vira um turno para cada trecho
     * entre as pausas.
     */
    private function convert_working_plans(): void
    {
        $providers = $this->db
            ->select('user_settings.id_users, user_settings.working_plan')
            ->from('user_settings')
            ->join('users', 'users.id = user_settings.id_users', 'inner')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get()
            ->result_array();

        $now = date('Y-m-d H:i:s');

        foreach ($providers as $provider) {
            $has_shifts = $this->db
                ->get_where('provider_shifts', ['id_users_provider' => $provider['id_users']])
                ->num_rows();

            if ($has_shifts) {
                continue;
            }

            $working_plan = json_decode((string) $provider['working_plan'], true) ?: [];

            foreach ($working_plan as $weekday => $day) {
                if (empty($day['start']) || empty($day['end'])) {
                    continue;
                }

                $breaks = array_values(array_filter($day['breaks'] ?? [], fn($b) => !empty($b['start']) && !empty($b['end'])));
                usort($breaks, fn($a, $b) => strcmp($a['start'], $b['start']));

                // Trechos de atendimento do dia, descontando as pausas.
                $pieces = [];
                $cursor = $day['start'];

                foreach ($breaks as $break) {
                    if ($break['start'] > $cursor) {
                        $pieces[] = [$cursor, min($break['start'], $day['end'])];
                    }

                    $cursor = max($cursor, $break['end']);
                }

                if ($cursor < $day['end']) {
                    $pieces[] = [$cursor, $day['end']];
                }

                // Dois trechos separados por uma pausa viram um turno com intervalo; o resto, um turno por trecho.
                $segments =
                    count($pieces) === 2
                        ? [[$pieces[0][0], $pieces[1][1], $pieces[0][1], $pieces[1][0]]]
                        : array_map(fn($piece) => [$piece[0], $piece[1], null, null], $pieces);

                foreach ($segments as [$start, $end, $break_start, $break_end]) {
                    $this->db->insert('provider_shifts', [
                        'id_users_provider' => $provider['id_users'],
                        'weekday' => $weekday,
                        'frequency' => 'weekly',
                        'start_time' => $start,
                        'end_time' => $end,
                        'break_start' => $break_start,
                        'break_end' => $break_end,
                        'duration' => self::DEFAULT_DURATION,
                        'create_datetime' => $now,
                        'update_datetime' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('provider_shifts')) {
            $this->db->query(
                'ALTER TABLE `' .
                    $this->db->dbprefix('provider_shifts') .
                    '` DROP FOREIGN KEY `provider_shifts_users_provider`',
            );

            $this->dbforge->drop_table('provider_shifts');
        }
    }
}
