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
        $this->load->model('clinical_record_types_model');
        $this->load->model('goal_templates_model');
        $this->load->model('customers_model');
        $this->load->model('roles_model');
        $this->load->model('services_model');

        $this->load->library('accounts');
        $this->load->library('permissions');

        $this->load->helper('clinical');
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

        $records = $this->visible_records($customer_id);

        $addable_types = $this->clinical_record_types_model->addable(session('role_slug'));

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
            'can_add' => (bool) $addable_types,
            'addable_types' => $addable_types,
            'editable_type_ids' => $this->editable_type_ids(),
            'catalog' => $this->clinical_records_model->catalog(),
            'goals_progress' => $this->clinical_records_model->goals_progress($records),
            'goal_labels' => $this->goal_labels($records),
            'current_user_id' => (int) session('user_id'),
            'success_message' => $this->session->flashdata('clinical_success'),
        ]);
    }

    /**
     * Relatório A4 para imprimir ou salvar em PDF: o prontuário inteiro ou um registro (?record=ID).
     */
    public function report(int $customer_id = 0): void
    {
        method('get');

        $customer = $this->authorize($customer_id, 'view');

        if (!$customer) {
            return;
        }

        $records = $this->visible_records($customer_id);
        $record_id = (int) request('record');

        if ($record_id) {
            $records = array_values(array_filter($records, fn($record) => $record['id'] === $record_id));

            if (!$records) {
                abort(404, 'Not Found');

                return;
            }
        }

        $this->clinical_records_model->log_access($this->current_user(), $customer_id, $record_id ?: null, 'print');

        $all_records = $this->visible_records($customer_id);

        html_vars([
            'page_title' => lang('cr_report_title'),
            'customer' => $customer,
            'records' => array_reverse($records), // Relatório em ordem cronológica.
            'single' => (bool) $record_id,
            'forms' => [
                'assessment' => $this->clinical_records_model->form('assessment'),
                'evolution' => $this->clinical_records_model->form('evolution'),
            ],
            'catalog' => $this->clinical_records_model->catalog(),
            'goals_progress' => $this->clinical_records_model->goals_progress($all_records),
            'goal_labels' => $this->goal_labels($all_records),
            'issued_by' => $this->current_user()['name'],
            'company_name' => setting('company_name'),
            'company_email' => setting('company_email'),
            'company_link' => setting('company_link'),
            'company_logo' => setting('company_logo'),
        ]);

        $this->load->view('pages/clinical_record_report');
    }

    /**
     * Formulário de novo registro de um tipo (clinical_records/create/{paciente}/{tipo}). Aceita também a ficha no
     * lugar do tipo (.../assessment ou .../evolution, endereços de antes dos tipos): usa o primeiro tipo liberado dela.
     */
    public function create(int $customer_id = 0, string $type = ''): void
    {
        method('get');

        $customer = $this->authorize($customer_id, 'add');

        if (!$customer) {
            return;
        }

        $role = session('role_slug');

        $record_type = ctype_digit($type)
            ? $this->clinical_record_types_model->find((int) $type)
            : $this->clinical_record_types_model->first_addable_for_form($type, $role);

        if (!$record_type || !$this->clinical_record_types_model->can_add($record_type['id'], $role)) {
            abort(403, 'Forbidden');

            return;
        }

        $appointment = $this->find_appointment((int) request('appointment'), $customer_id);

        $this->render_form($customer, [
            'id' => null,
            'type' => $record_type['form'],
            'id_clinical_record_types' => $record_type['id'],
            'type_name' => $record_type['name'],
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

            if (!$this->authorize($customer_id, 'add')) {
                return;
            }

            $record_type = $this->clinical_record_types_model->find((int) request('record_type'));

            if (
                !$record_type ||
                !$this->clinical_record_types_model->can_add($record_type['id'], session('role_slug'))
            ) {
                abort(403, 'Forbidden');

                return;
            }

            $type = $record_type['form'];
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
                        'id_clinical_record_types' => $record_type['id'],
                        'id_users_customer' => $customer_id,
                        'id_appointments' => $appointment['id'] ?? null,
                        'record_date' => $record_date,
                        'pain_scale' => $pain_scale,
                        'content' => $content,
                    ],
                    $this->current_user(),
                );

                if ($appointment) {
                    $this->mark_appointment_completed((int) $appointment['id']);
                }
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
                    'id_clinical_record_types' => $record_id ? $record['id_clinical_record_types'] : $record_type['id'],
                    'type_name' => $record_id ? $record['type_name'] : $record_type['name'],
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

        if (
            $record['id_users_author'] !== (int) session('user_id') ||
            !in_array($this->clinical_record_types_model->type_id_for($record), $this->editable_type_ids(), true)
        ) {
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

        $type_name = $record['type_name'] ?? '' ?: lang('cr_' . $type);

        $this->render('pages/clinical_record_form', [
            'page_title' => lang($record['id'] ? 'cr_edit_record' : 'cr_new_record') . ': ' . $type_name,
            'customer' => $customer,
            'record' => $record,
            'form' => $this->clinical_records_model->form($type),
            'catalog' => $catalog,
            'region_blocks' => $this->clinical_records_model->region_blocks(array_keys($catalog['regions']), $catalog),
            'goals_progress' => $this->goals_progress((int) $customer['id'], (int) ($record['id'] ?? 0)),
            'goal_templates' => $this->goal_templates_model->get_all(),
            'error_message' => $error,
        ]);
    }

    /**
     * Metas da avaliação mais recente e quando cada uma foi atingida, sem contar o registro em edição.
     */
    private function goals_progress(int $customer_id, int $exclude_record_id = 0): array
    {
        $records = array_filter(
            $this->visible_records($customer_id),
            fn($record) => $record['id'] !== $exclude_record_id,
        );

        return $this->clinical_records_model->goals_progress(array_values($records));
    }

    /**
     * Registros do paciente que o perfil do usuário pode ver (Configurações → Prontuário).
     */
    private function visible_records(int $customer_id): array
    {
        $viewable = $this->clinical_record_types_model->viewable_ids(session('role_slug'));

        $records = [];

        foreach ($this->clinical_records_model->get_by_customer($customer_id) as $record) {
            // Registro antigo sem tipo fica com o primeiro tipo da mesma ficha.
            $record['id_clinical_record_types'] = $this->clinical_record_types_model->type_id_for($record);

            if (in_array($record['id_clinical_record_types'], $viewable, true)) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Tipos em que o perfil do usuário pode adicionar e, por isso, editar os próprios registros (inclui desativados).
     *
     * @return int[]
     */
    private function editable_type_ids(): array
    {
        $role = session('role_slug');

        return array_values(
            array_map(
                fn($type) => $type['id'],
                array_filter(
                    $this->clinical_record_types_model->get_all(),
                    fn($type) => !empty($type['permissions'][$role]['add']),
                ),
            ),
        );
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
     * Registro novo feito a partir da consulta = atendimento terminado: a consulta passa para "Executado" (situação
     * "completed" de application/config/appointment_statuses.php), a não ser que já esteja numa situação que fica
     * (Executado, Faltou, Cancelado) ou que a situação não exista na lista. Só o profissional da consulta (ou quem pode
     * mexer na agenda dele) muda a situação.
     */
    private function mark_appointment_completed(int $appointment_id): void
    {
        $completed = appointment_status_automation('completed');

        if (!$completed) {
            return;
        }

        try {
            $appointment = $this->appointments_model->find($appointment_id);

            if (
                appointment_status_keeps_on_completion($appointment['status'] ?? null) ||
                !can('edit', PRIV_APPOINTMENTS) ||
                !can_manage_provider((int) $appointment['id_users_provider'])
            ) {
                return;
            }

            $appointment['status'] = $completed;

            $this->appointments_model->save($appointment);
        } catch (Throwable $e) {
            // O registro já foi salvo; a situação da consulta é só um complemento.
            log_message(
                'error',
                'Could not mark appointment ' . $appointment_id . ' as completed: ' . $e->getMessage(),
            );
        }
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
