<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Prontuário do paciente: avaliação fisioterapêutica e evoluções.
 *
 * Só quem tem a permissão "clinical_records" (profissional de saúde) acessa. Administrador e recepcionista não veem
 * o conteúdo clínico. Todo acesso e alteração fica registrado.
 *
 * Rotas:
 *  - clinical_records/patient/{paciente}[?appointment={consulta}]  prontuário (linha do tempo)
 *  - clinical_records/create/{paciente}/{assessment|evolution}       nova avaliação ou evolução
 *  - clinical_records/edit/{registro}                                editar (só o autor)
 *  - clinical_records/save (POST)                                    salvar
 */
class Clinical_records extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('clinical_records_model');
        $this->load->model('customers_model');
        $this->load->model('roles_model');
        $this->load->model('services_model');

        $this->load->library('accounts');
        $this->load->library('permissions');
    }

    /**
     * Prontuário do paciente.
     */
    public function patient(int $customer_id = 0): void
    {
        method('get');

        $customer = $this->authorize($customer_id, 'view');

        if (!$customer) {
            return;
        }

        $appointment = $this->find_appointment((int) request('appointment'), $customer_id);

        $records = $this->clinical_records_model->get_by_customer($customer_id);

        $this->clinical_records_model->log_access($this->current_user(), $customer_id, null, 'view');

        $this->render('pages/clinical_record', [
            'page_title' => lang('clinical_record') . ' · ' . trim($customer['first_name'] . ' ' . $customer['last_name']),
            'customer' => $customer,
            'appointment' => $appointment,
            'records' => $records,
            'versions' => $this->versions_by_record($records),
            'access_log' => $this->clinical_records_model->get_access_log($customer_id),
            'forms' => [
                'assessment' => $this->clinical_records_model->form('assessment'),
                'evolution' => $this->clinical_records_model->form('evolution'),
            ],
            'can_add' => can('add', PRIV_CLINICAL_RECORDS),
            'catalog' => $this->clinical_records_model->catalog(),
            'goals_progress' => $this->clinical_records_model->goals_progress($records),
            'goal_labels' => $this->goal_labels($records),
            'current_user_id' => (int) session('user_id'),
            'success_message' => $this->session->flashdata('clinical_success'),
        ]);
    }

    /**
     * Formulário de nova avaliação ou evolução.
     */
    public function create(int $customer_id = 0, string $type = 'evolution'): void
    {
        method('get');

        $customer = $this->authorize($customer_id, 'add');

        if (!$customer) {
            return;
        }

        $appointment = $this->find_appointment((int) request('appointment'), $customer_id);

        $this->render_form($customer, [
            'id' => null,
            'type' => $type,
            'id_appointments' => $appointment['id'] ?? null,
            'record_date' => $appointment['start_datetime'] ?? date('Y-m-d H:i:s'),
            'content' => [],
        ]);
    }

    /**
     * Formulário de edição (só o autor do registro).
     */
    public function edit(int $record_id = 0): void
    {
        method('get');

        $record = $this->find_record_for_edit($record_id);

        if (!$record) {
            return;
        }

        $customer = $this->customers_model->find($record['id_users_customer']);

        $this->render_form($customer, $record);
    }

    /**
     * Salva uma nova avaliação/evolução ou a edição de uma existente.
     */
    public function save(): void
    {
        method('post');

        $record_id = (int) request('id');
        $input = (array) request('fields', []);
        $record_date = $this->parse_record_date((string) request('record_date'));

        if ($record_id) {
            $record = $this->find_record_for_edit($record_id);

            if (!$record) {
                return;
            }

            $customer_id = $record['id_users_customer'];
            $type = $record['type'];
        } else {
            $customer_id = (int) request('id_users_customer');
            $type = (string) request('type');

            if (!$this->authorize($customer_id, 'add')) {
                return;
            }
        }

        try {
            $form = $this->clinical_records_model->form($type);

            if (!$record_date) {
                throw new InvalidArgumentException(lang('clinical_record_invalid_date'));
            }

            $goals = $this->goals_progress($customer_id, $record_id)['goals'];

            [$content, $pain_scale] = $this->clinical_records_model->clean_content($form, $input, [
                'catalog' => $this->clinical_records_model->catalog(),
                'goal_ids' => array_column($goals, 'id'),
            ]);

            if ($record_id) {
                $appointment_id = $record['id_appointments'] ?: null;

                $this->clinical_records_model->update_content(
                    $record,
                    $record_date,
                    $content,
                    $pain_scale,
                    $this->current_user(),
                );
            } else {
                $appointment = $this->find_appointment((int) request('id_appointments'), $customer_id);
                $appointment_id = $appointment['id'] ?? null;

                $this->clinical_records_model->create(
                    [
                        'type' => $type,
                        'id_users_customer' => $customer_id,
                        'id_appointments' => $appointment['id'] ?? null,
                        'record_date' => $record_date,
                        'pain_scale' => $pain_scale,
                        'content' => $content,
                    ],
                    $this->current_user(),
                );
            }

            $this->session->set_flashdata('clinical_success', lang('clinical_record_saved'));

            // Volta ao prontuário mantendo a consulta de origem, para registrar o resto do atendimento.
            redirect('clinical_records/patient/' . $customer_id . ($appointment_id ? '?appointment=' . $appointment_id : ''));
        } catch (InvalidArgumentException $e) {
            $customer = $this->customers_model->find($customer_id);

            $this->render_form(
                $customer,
                [
                    'id' => $record_id ?: null,
                    'type' => $type,
                    'id_appointments' => (int) request('id_appointments') ?: null,
                    'record_date' => $record_date ?: date('Y-m-d H:i:s'),
                    'content' => $input,
                ],
                $e->getMessage(),
            );
        }
    }

    /**
     * Confere a permissão no prontuário e o acesso ao paciente. Devolve o paciente ou null (já respondeu 403 ou
     * mandou para o login).
     */
    private function authorize(int $customer_id, string $action): ?array
    {
        $user_id = (int) session('user_id');

        if (!$user_id) {
            session(['dest_url' => current_url()]);
            redirect('login');

            return null;
        }

        if (cannot($action, PRIV_CLINICAL_RECORDS) || !$customer_id) {
            abort(403, 'Forbidden');

            return null;
        }

        try {
            $customer = $this->customers_model->find($customer_id);
        } catch (InvalidArgumentException) {
            abort(404, 'Not Found');

            return null;
        }

        if (!$this->permissions->has_customer_access($user_id, $customer_id)) {
            abort(403, 'Forbidden');

            return null;
        }

        return $customer;
    }

    /**
     * Registro que o usuário atual pode editar (precisa ser o autor), ou null.
     */
    private function find_record_for_edit(int $record_id): ?array
    {
        try {
            $record = $this->clinical_records_model->find($record_id);
        } catch (InvalidArgumentException) {
            if (session('user_id')) {
                abort(404, 'Not Found');
            } else {
                redirect('login');
            }

            return null;
        }

        if (!$this->authorize($record['id_users_customer'], 'edit')) {
            return null;
        }

        if ($record['id_users_author'] !== (int) session('user_id')) {
            abort(403, 'Forbidden');

            return null;
        }

        return $record;
    }

    private function render_form(array $customer, array $record, ?string $error = null): void
    {
        $type = in_array($record['type'], Clinical_records_model::TYPES, true) ? $record['type'] : 'evolution';

        $record['type'] = $type;

        $catalog = $this->clinical_records_model->catalog();

        $this->render('pages/clinical_record_form', [
            'page_title' => lang($record['id'] ? 'cr_edit_' . $type : 'cr_new_' . $type),
            'customer' => $customer,
            'record' => $record,
            'form' => $this->clinical_records_model->form($type),
            'catalog' => $catalog,
            'region_blocks' => $this->clinical_records_model->region_blocks(array_keys($catalog['regions']), $catalog),
            'goals_progress' => $this->goals_progress((int) $customer['id'], (int) ($record['id'] ?? 0)),
            'error_message' => $error,
        ]);
    }

    /**
     * Metas da avaliação mais recente e quando cada uma foi atingida, sem contar o registro em edição.
     */
    private function goals_progress(int $customer_id, int $exclude_record_id = 0): array
    {
        $records = array_filter(
            $this->clinical_records_model->get_by_customer($customer_id),
            fn($record) => $record['id'] !== $exclude_record_id,
        );

        return $this->clinical_records_model->goals_progress(array_values($records));
    }

    private function render(string $view, array $vars): void
    {
        $role_slug = session('role_slug');

        html_vars(
            array_merge(
                [
                    'active_menu' => PRIV_CUSTOMERS,
                    'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
                    'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
                ],
                $vars,
            ),
        );

        $this->load->view($view);
    }

    /**
     * Consulta do paciente (para ligar o registro ao atendimento), ou null.
     */
    private function find_appointment(int $appointment_id, int $customer_id): ?array
    {
        if (!$appointment_id) {
            return null;
        }

        $appointment = $this->appointments_model
            ->query()
            ->where(['id' => $appointment_id, 'id_users_customer' => $customer_id])
            ->get()
            ->row_array();

        if (!$appointment) {
            return null;
        }

        $service = $this->db->get_where('services', ['id' => $appointment['id_services']])->row_array();

        $appointment['service_name'] = $service['name'] ?? '';

        return $appointment;
    }

    /**
     * Texto de cada meta (de todas as avaliações), para mostrar as metas atingidas nas evoluções.
     */
    private function goal_labels(array $records): array
    {
        $labels = [];

        foreach ($records as $record) {
            foreach ($record['content']['goals'] ?? [] as $goal) {
                $labels[$goal['id']] ??= $goal['goal'];
            }
        }

        return $labels;
    }

    private function versions_by_record(array $records): array
    {
        $versions = [];

        foreach ($records as $record) {
            if (!empty($record['versions_count'])) {
                $versions[$record['id']] = $this->clinical_records_model->get_versions($record['id']);
            }
        }

        return $versions;
    }

    /**
     * Converte a data/hora do formulário (datetime-local: AAAA-MM-DDTHH:MM) para o banco, ou null se inválida.
     */
    private function parse_record_date(string $value): ?string
    {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $value) ?: DateTime::createFromFormat('Y-m-d H:i:s', $value);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    private function current_user(): array
    {
        $user_id = (int) session('user_id');

        return ['id' => $user_id, 'name' => $this->accounts->get_user_display_name($user_id)];
    }
}
