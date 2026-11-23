<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: status das consultas em português.
 *
 * Os status são texto livre, configurável (o primeiro da lista é o status de uma consulta nova). Se a clínica ainda
 * usa a lista padrão em inglês, ela é traduzida e as consultas existentes acompanham a tradução.
 */
class Migration_Translate_appointment_status_options extends EA_Migration
{
    const TRANSLATIONS = [
        'Booked' => 'Agendado',
        'Confirmed' => 'Confirmado',
        'Rescheduled' => 'Remarcado',
        'Cancelled' => 'Cancelado',
        'Draft' => 'Rascunho',
    ];

    public function up(): void
    {
        $this->translate(self::TRANSLATIONS);
    }

    public function down(): void
    {
        $this->translate(array_flip(self::TRANSLATIONS));
    }

    private function translate(array $map): void
    {
        $setting = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        $options = json_decode((string) ($setting['value'] ?? ''), true);

        // Só mexe se a lista for exatamente a padrão (não sobrescreve uma lista que a clínica já personalizou).
        if (!is_array($options) || array_values($options) !== array_keys($map)) {
            return;
        }

        $this->db->update(
            'settings',
            ['value' => json_encode(array_values($map), JSON_UNESCAPED_UNICODE)],
            ['name' => 'appointment_status_options'],
        );

        foreach ($map as $from => $to) {
            $this->db->update('appointments', ['status' => $to], ['status' => $from]);
        }
    }
}
