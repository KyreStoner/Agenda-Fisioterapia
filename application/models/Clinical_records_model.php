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
     * Catálogo de regiões do corpo, testes especiais e questionários (application/config/clinical_catalog.php).
     */
    public function catalog(): array
    {
        $this->config->load('clinical_catalog', true);

        return $this->config->item('clinical_catalog', 'clinical_catalog');
    }

    /**
     * Blocos de testes para as regiões marcadas: um por área e lado (ex.: "ombro_d" = Ombro direito). Regiões da
     * mesma área e do mesmo lado (ombro e braço direitos) formam um bloco só.
     *
     * @return array [chave => ['area' => ..., 'side' => ..., 'label' => ...]] na ordem do catálogo.
     */
    public function region_blocks(array $regions, array $catalog): array
    {
        $blocks = [];

        foreach ($catalog['regions'] ?? [] as $region_id => $region) {
            if (!in_array($region_id, $regions, true) || empty($region['area'])) {
                continue;
            }

            $key = $region['area'] . ($region['side'] ? '_' . $region['side'] : '');

            if (isset($blocks[$key])) {
                continue;
            }

            $side = match ($region['side']) {
                'd' => ' ' . lang('cr_side_right'),
                'e' => ' ' . lang('cr_side_left'),
                default => '',
            };

            $blocks[$key] = [
                'area' => $region['area'],
                'side' => $region['side'],
                'label' => $catalog['areas'][$region['area']]['label'] . $side,
            ];
        }

        return $blocks;
    }

    /**
     * Valida e limpa os campos enviados pelo formulário. Devolve [conteúdo, escala de dor].
     *
     * @param array $form Seções e campos da ficha.
     * @param array $input Valores enviados (fields[...]).
     * @param array $context 'catalog' (catálogo clínico) e 'goal_ids' (metas válidas para marcar na evolução).
     *
     * @throws InvalidArgumentException Com a mensagem para o usuário.
     */
    public function clean_content(array $form, array $input, array $context = []): array
    {
        $fields = array_merge(...array_values($form));
        $catalog = $context['catalog'] ?? null;
        $content = [];
        $pain_scale = null;
        $missing = [];

        // O mapa corporal vem primeiro: os testes por região dependem das regiões marcadas.
        uasort($fields, fn($a, $b) => ($a['type'] === 'body_map' ? 0 : 1) <=> ($b['type'] === 'body_map' ? 0 : 1));

        foreach ($fields as $name => $field) {
            $raw = $input[$name] ?? '';

            switch ($field['type']) {
                case 'body_map':
                    $selected = array_map('strval', is_array($raw) ? $raw : []);
                    $value = array_values(array_filter(array_keys($catalog['regions'] ?? []), fn($id) => in_array($id, $selected, true)));
                    break;

                case 'region_tests':
                    $value = $this->clean_region_tests($raw, $content[$field['regions_field']] ?? [], $catalog ?? []);
                    break;

                case 'goals':
                    $value = $this->clean_goals($raw);
                    break;

                case 'goals_check':
                    $valid = $context['goal_ids'] ?? [];
                    $value = array_values(array_intersect($valid, array_map('strval', is_array($raw) ? $raw : [])));
                    break;

                case 'pain_scale':
                case 'number':
                    $value = trim((string) (is_array($raw) ? '' : $raw));

                    if ($value !== '') {
                        $number = filter_var($value, FILTER_VALIDATE_INT);
                        $max = $field['type'] === 'pain_scale' ? 10 : 999;

                        if ($number === false || $number < 0 || $number > $max) {
                            throw new InvalidArgumentException(lang('clinical_record_invalid_number') . ' ' . lang('cr_field_' . $name));
                        }

                        $value = $number;
                    }

                    if ($field['type'] === 'pain_scale') {
                        $pain_scale = $value === '' ? null : $value;
                    }
                    break;

                default:
                    $value = trim((string) (is_array($raw) ? '' : $raw));

                    if (mb_strlen($value) > self::MAX_TEXT_LENGTH) {
                        throw new InvalidArgumentException(lang('clinical_record_text_too_long') . ' ' . lang('cr_field_' . $name));
                    }
            }

            if (!empty($field['required']) && ($value === '' || $value === [])) {
                $missing[] = lang('cr_field_' . $name);
            }

            $content[$name] = $value;
        }

        // Devolve na ordem da ficha.
        $ordered = [];

        foreach ($form as $section_fields) {
            foreach (array_keys($section_fields) as $name) {
                $ordered[$name] = $content[$name];
            }
        }

        if ($missing) {
            $names = [];

            foreach ($form as $section_fields) {
                foreach ($section_fields as $name => $field) {
                    if (in_array(lang('cr_field_' . $name), $missing, true)) {
                        $names[] = lang('cr_field_' . $name);
                    }
                }
            }

            throw new InvalidArgumentException(lang('clinical_record_required_fields') . ' ' . implode(', ', $names) . '.');
        }

        return [$ordered, $pain_scale];
    }

    /**
     * Resultados dos testes especiais e pontuação dos questionários, só das áreas das regiões marcadas.
     */
    private function clean_region_tests(mixed $raw, array $regions, array $catalog): array
    {
        $raw = is_array($raw) ? $raw : [];
        $clean = [];

        foreach ($this->region_blocks($regions, $catalog) as $key => $block) {
            $area = $catalog['areas'][$block['area']];
            $tests = [];
            $questionnaires = [];

            foreach (array_keys($area['tests']) as $test_id) {
                $result = (string) ($raw[$key]['tests'][$test_id] ?? '');

                if (isset($catalog['test_results'][$result])) {
                    $tests[$test_id] = $result;
                }
            }

            foreach (array_keys($area['questionnaires']) as $questionnaire_id) {
                $score = trim((string) ($raw[$key]['questionnaires'][$questionnaire_id] ?? ''));

                if ($score !== '') {
                    $questionnaires[$questionnaire_id] = mb_substr($score, 0, 50);
                }
            }

            if ($tests || $questionnaires) {
                $clean[$key] = array_filter(['tests' => $tests, 'questionnaires' => $questionnaires]);
            }
        }

        return $clean;
    }

    /**
     * Metas por semana: [id, semana, meta, como atingir]. Linhas sem meta são ignoradas.
     */
    private function clean_goals(mixed $raw): array
    {
        $goals = [];

        foreach (is_array($raw) ? $raw : [] as $row) {
            $goal = trim((string) ($row['goal'] ?? ''));

            if ($goal === '') {
                continue;
            }

            $week = trim((string) ($row['week'] ?? ''));
            $week_number = $week === '' ? null : filter_var($week, FILTER_VALIDATE_INT);

            if ($week_number === false || ($week_number !== null && ($week_number < 1 || $week_number > 104))) {
                throw new InvalidArgumentException(lang('cr_goal_invalid_week'));
            }

            $id = (string) ($row['id'] ?? '');

            $goals[] = [
                'id' => preg_match('/^[a-z0-9]{6,20}$/', $id) ? $id : bin2hex(random_bytes(5)),
                'week' => $week_number,
                'goal' => mb_substr($goal, 0, 500),
                'plan' => mb_substr(trim((string) ($row['plan'] ?? '')), 0, 2000),
            ];
        }

        usort($goals, fn($a, $b) => ($a['week'] ?? 999) <=> ($b['week'] ?? 999));

        return $goals;
    }

    /**
     * Metas da avaliação mais recente do paciente e, para cada uma, a data da evolução em que foi atingida.
     *
     * @return array ['assessment' => registro ou null, 'goals' => [...], 'achieved' => [id da meta => data]]
     */
    public function goals_progress(array $records): array
    {
        $assessment = null;

        foreach ($records as $record) {
            if ($record['type'] === 'assessment' && !empty($record['content']['goals'])) {
                $assessment = $record;
                break; // Os registros vêm do mais recente para o mais antigo.
            }
        }

        $achieved = [];

        foreach (array_reverse($records) as $record) {
            if ($record['type'] !== 'evolution') {
                continue;
            }

            foreach ($record['content']['goals_achieved'] ?? [] as $goal_id) {
                $achieved[$goal_id] ??= $record['record_date'];
            }
        }

        return [
            'assessment' => $assessment,
            'goals' => $assessment['content']['goals'] ?? [],
            'achieved' => $achieved,
        ];
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
