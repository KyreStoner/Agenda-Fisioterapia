<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: situações dos atendimentos com cor (a agenda passa a ser pintada pela situação, pedido do usuário).
 *
 *  - Se a clínica ainda usa a lista padrão (a da migração 073 ou a original em inglês), ela vira a lista nova, no
 *    caminho do atendimento: Marcado, Encaixado, Confirmado, Aguardando, Em atendimento, Executado, Faltou, Cancelado.
 *    As consultas existentes acompanham (Agendado, Remarcado e Rascunho viram Marcado). Lista personalizada não muda.
 *  - Nova configuração "appointment_status_colors" (nome → cor).
 *
 * A lista e as cores ficam aqui fixas de propósito (uma migração não muda depois de publicada); a lista padrão que o
 * sistema usa como referência está em application/config/appointment_statuses.php.
 */
class Migration_Appointment_status_colors extends EA_Migration
{
    const NEW_STATUSES = [
        'Marcado' => '#7cbae8',
        'Encaixado' => '#f3bc7d',
        'Confirmado' => '#82e4ec',
        'Aguardando' => '#ebe07c',
        'Em atendimento' => '#c9b3f5',
        'Executado' => '#abe9a4',
        'Faltou' => '#f3aea6',
        'Cancelado' => '#d5d5d5',
    ];

    const OLD_DEFAULT_LISTS = [
        ['Agendado', 'Confirmado', 'Remarcado', 'Cancelado', 'Rascunho'],
        ['Booked', 'Confirmed', 'Rescheduled', 'Cancelled', 'Draft'],
    ];

    const RENAME = [
        'Agendado' => 'Marcado',
        'Remarcado' => 'Marcado',
        'Rascunho' => 'Marcado',
        'Booked' => 'Marcado',
        'Rescheduled' => 'Marcado',
        'Draft' => 'Marcado',
        'Confirmed' => 'Confirmado',
        'Cancelled' => 'Cancelado',
    ];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $setting = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        $options = json_decode((string) ($setting['value'] ?? ''), true);

        if (is_array($options) && in_array(array_values($options), self::OLD_DEFAULT_LISTS, true)) {
            $this->db->update(
                'settings',
                ['value' => json_encode(array_keys(self::NEW_STATUSES), JSON_UNESCAPED_UNICODE)],
                ['name' => 'appointment_status_options'],
            );

            foreach (self::RENAME as $from => $to) {
                $this->db->update('appointments', ['status' => $to], ['status' => $from, 'is_unavailability' => 0]);
            }
        }

        if (!$this->db->get_where('settings', ['name' => 'appointment_status_colors'])->num_rows()) {
            $this->db->insert('settings', [
                'name' => 'appointment_status_colors',
                'value' => json_encode(self::NEW_STATUSES, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->db->delete('settings', ['name' => 'appointment_status_colors']);

        $setting = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        $options = json_decode((string) ($setting['value'] ?? ''), true);

        if (is_array($options) && array_values($options) === array_keys(self::NEW_STATUSES)) {
            $this->db->update(
                'settings',
                ['value' => json_encode(self::OLD_DEFAULT_LISTS[0], JSON_UNESCAPED_UNICODE)],
                ['name' => 'appointment_status_options'],
            );

            $this->db->update('appointments', ['status' => 'Agendado'], ['status' => 'Marcado']);
        }
    }
}
