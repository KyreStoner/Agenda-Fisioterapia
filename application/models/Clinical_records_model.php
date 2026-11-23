<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Prontuário: avaliações e evoluções dos pacientes.
 *
 * Regras:
 *  - os campos de cada ficha vêm de application/config/clinical_forms.php e são guardados em JSON ("content");
 *  - a escala de dor também fica em coluna própria, para acompanhar a evolução;
 *  - registros não são excluídos; cada edição guarda a versão anterior em clinical_record_versions;
 *  - todo acesso e alteração é registrado em clinical_access_log.
 */
class Clinical_records_model extends EA_Model
{
    const TYPES = ['assessment', 'evolution'];

    const MAX_TEXT_LENGTH = 20000;

    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'id_users_author' => 'integer',
        'id_appointments' => 'integer',
    ];

    /**
     * Seções e campos de uma ficha ("assessment" ou "evolution").
     */
    public function form(string $type): array
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(lang('clinical_record_invalid_type'));
        }

        $this->config->load('clinical_forms', true);

        return $this->config->item('clinical_forms', 'clinical_forms')[$type];
    }

    /**
     * Valida e limpa os campos enviados pelo formulário. Devolve [conteúdo, escala de dor].
     *
     * @throws InvalidArgumentException Com a mensagem para o usuário.
     */
    public function clean_content(array $form, array $input): array
    {
        $content = [];
        $pain_scale = null;
        $missing = [];

        foreach ($form as $fields) {
            foreach ($fields as $name => $field) {
                $value = trim((string) ($input[$name] ?? ''));

                if ($field['type'] === 'pain_scale' || $field['type'] === 'number') {
                    if ($value !== '') {
                        $number = filter_var($value, FILTER_VALIDATE_INT);
                        $max = $field['type'] === 'pain_scale' ? 10 : 999;

                        if ($number === false || $number < 0 || $number > $max) {
                            throw new InvalidArgumentException(lang('clinical_record_invalid_number') . ' ' . lang('cr_field_' . $name));
                        }

                        $value = $number;
                    }
                } elseif (mb_strlen($value) > self::MAX_TEXT_LENGTH) {
                    throw new InvalidArgumentException(lang('clinical_record_text_too_long') . ' ' . lang('cr_field_' . $name));
                }

                if (!empty($field['required']) && $value === '') {
                    $missing[] = lang('cr_field_' . $name);
                }

                if ($field['type'] === 'pain_scale') {
                    $pain_scale = $value === '' ? null : $value;
                }

                $content[$name] = $value;
            }
        }

        if ($missing) {
            throw new InvalidArgumentException(lang('clinical_record_required_fields') . ' ' . implode(', ', $missing) . '.');
        }

        return [$content, $pain_scale];
    }

    /**
     * Cria um registro e devolve o ID.
     */
    public function create(array $record, array $author): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->insert('clinical_records', [
            'type' => $record['type'],
            'id_users_customer' => $record['id_users_customer'],
            'id_users_author' => $author['id'],
            'author_name' => $author['name'],
            'id_appointments' => $record['id_appointments'] ?: null,
            'record_date' => $record['record_date'],
            'pain_scale' => $record['pain_scale'],
            'content' => json_encode($record['content'], JSON_UNESCAPED_UNICODE),
            'create_datetime' => $now,
            'update_datetime' => $now,
        ]);

        $record_id = (int) $this->db->insert_id();

        $this->log_access($author, $record['id_users_customer'], $record_id, 'create');

        return $record_id;
    }

    /**
     * Atualiza um registro, guardando antes a versão anterior.
     */
    public function update_content(array $record, string $record_date, array $content, ?int $pain_scale, array $editor): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->db->insert('clinical_record_versions', [
            'id_clinical_records' => $record['id'],
            'record_date' => $record['record_date'],
            'pain_scale' => $record['pain_scale'],
            'content' => json_encode($record['content'], JSON_UNESCAPED_UNICODE),
            'id_users' => $editor['id'],
            'user_name' => $editor['name'],
            'create_datetime' => $now,
        ]);

        $this->db->update(
            'clinical_records',
            [
                'record_date' => $record_date,
                'pain_scale' => $pain_scale,
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'update_datetime' => $now,
            ],
            ['id' => $record['id']],
        );

        $this->db->trans_complete();

        $this->log_access($editor, $record['id_users_customer'], $record['id'], 'update');
    }

    public function find(int $record_id): array
    {
        $record = $this->db->get_where('clinical_records', ['id' => $record_id])->row_array();

        if (!$record) {
            throw new InvalidArgumentException(lang('clinical_record_not_found'));
        }

        return $this->decode($record);
    }

    /**
     * Registros de um paciente, do mais recente para o mais antigo, com a quantidade de versões anteriores.
     */
    public function get_by_customer(int $customer_id): array
    {
        $records_table = $this->db->dbprefix('clinical_records');
        $versions_table = $this->db->dbprefix('clinical_record_versions');

        $records = $this->db
            ->query(
                "SELECT r.*, (SELECT COUNT(*) FROM `$versions_table` v WHERE v.id_clinical_records = r.id) AS versions_count
                 FROM `$records_table` r
                 WHERE r.id_users_customer = ?
                 ORDER BY r.record_date DESC, r.id DESC",
                [$customer_id],
            )
            ->result_array();

        return array_map(fn($record) => $this->decode($record), $records);
    }

    public function get_versions(int $record_id): array
    {
        $versions = $this->db
            ->order_by('id', 'DESC')
            ->get_where('clinical_record_versions', ['id_clinical_records' => $record_id])
            ->result_array();

        foreach ($versions as &$version) {
            $version['content'] = json_decode($version['content'], true) ?: [];
        }

        return $versions;
    }

    public function customer_has_records(int $customer_id): bool
    {
        return $this->db->get_where('clinical_records', ['id_users_customer' => $customer_id], 1)->num_rows() > 0;
    }

    /**
     * Registra quem acessou ou alterou o prontuário de um paciente.
     *
     * @param array $user ['id' => ..., 'name' => ...]
     * @param string $action view | create | update
     */
    public function log_access(array $user, int $customer_id, ?int $record_id, string $action): void
    {
        $this->db->insert('clinical_access_log', [
            'id_users' => $user['id'],
            'user_name' => $user['name'],
            'id_users_customer' => $customer_id,
            'id_clinical_records' => $record_id,
            'action' => $action,
            'ip_address' => $this->input->ip_address(),
            'create_datetime' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_access_log(int $customer_id, int $limit = 30): array
    {
        return $this->db
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get_where('clinical_access_log', ['id_users_customer' => $customer_id])
            ->result_array();
    }

    private function decode(array $record): array
    {
        $this->cast($record);

        $record['content'] = json_decode($record['content'], true) ?: [];
        $record['pain_scale'] = $record['pain_scale'] === null ? null : (int) $record['pain_scale'];

        return $record;
    }
}
