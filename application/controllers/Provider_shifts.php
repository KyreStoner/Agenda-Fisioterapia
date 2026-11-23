<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Turnos de atendimento dos profissionais (Usuários → Turnos).
 *
 * Página simples, renderizada no servidor: lista dos turnos do profissional escolhido e formulário para adicionar ou
 * editar um turno. Quem pode editar usuários (administrador) pode configurar os turnos.
 */
class Provider_shifts extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('provider_shifts_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
        $this->load->library('shifts');
    }

    /**
     * Lista os turnos do profissional e mostra o formulário (novo turno ou edição).
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('provider_shifts')]);

        if (!$this->allow('view')) {
            return;
        }

        $providers = $this->providers_model->get(null, null, null, 'first_name ASC, last_name ASC');

        $provider_id = (int) request('provider', $providers[0]['id'] ?? 0);

        $provider = null;

        foreach ($providers as $item) {
            if ((int) $item['id'] === $provider_id) {
                $provider = $item;
            }
        }

        $shifts = $provider ? $this->provider_shifts_model->get_by_provider($provider_id) : [];

        $edit_id = (int) request('edit', 0);

        $form = $this->session->flashdata('shift_old') ?: null;

        if (!$form && $edit_id) {
            foreach ($shifts as $shift) {
                if ($shift['id'] === $edit_id) {
                    $form = $shift;
                }
            }
        }

        $form = array_merge(
            [
                'id' => null,
                'weekday' => 'weekdays',
                'frequency' => 'weekly',
                'start_time' => '',
                'end_time' => '',
                'break_start' => '',
                'break_end' => '',
                'duration' => 60,
                'valid_from' => '',
                'valid_until' => '',
                'notes' => '',
            ],
            $form ?? [],
        );

        html_vars([
            'page_title' => lang('shifts'),
            'active_menu' => PRIV_USERS,
            'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
            'providers' => $providers,
            'provider' => $provider,
            'shifts' => $shifts,
            'form' => $form,
            'can_edit' => can('edit', PRIV_USERS),
            'uses_shifts' => (bool) $shifts,
            'success_message' => $this->session->flashdata('shift_success'),
            'error_message' => $this->session->flashdata('shift_error'),
        ]);

        $this->load->view('pages/provider_shifts');
    }

    /**
     * Cria ou atualiza um turno (POST do formulário).
     */
    public function save(): void
    {
        method('post');

        if (!$this->allow('edit')) {
            return;
        }

        $provider_id = (int) request('id_users_provider');

        $shift = [
            'id' => request('id'),
            'id_users_provider' => $provider_id,
            'weekday' => request('weekday'),
            'frequency' => request('frequency'),
            'start_time' => request('start_time'),
            'end_time' => request('end_time'),
            'break_start' => request('break_start'),
            'break_end' => request('break_end'),
            'duration' => request('duration'),
            'valid_from' => request('valid_from'),
            'valid_until' => request('valid_until'),
            'notes' => request('notes'),
        ];

        try {
            $this->providers_model->find($provider_id);

            if (!empty($shift['id'])) {
                $existing = $this->provider_shifts_model->find((int) $shift['id']);

                if ($existing['id_users_provider'] !== $provider_id) {
                    throw new InvalidArgumentException(lang('shift_not_found'));
                }
            }

            $this->provider_shifts_model->save($shift);

            $this->shifts->sync_working_plan($provider_id);

            $this->session->set_flashdata('shift_success', lang('shift_saved'));

            redirect('provider_shifts?provider=' . $provider_id);
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('shift_error', $e->getMessage());
            $this->session->set_flashdata('shift_old', $shift);

            redirect(
                'provider_shifts?provider=' . $provider_id . (!empty($shift['id']) ? '&edit=' . (int) $shift['id'] : ''),
            );
        }
    }

    /**
     * Exclui um turno (POST).
     */
    public function delete(): void
    {
        method('post');

        if (!$this->allow('edit')) {
            return;
        }

        $shift = $this->provider_shifts_model->find((int) request('id'));

        $this->provider_shifts_model->delete($shift['id']);

        if ($this->provider_shifts_model->provider_has_shifts($shift['id_users_provider'])) {
            $this->shifts->sync_working_plan($shift['id_users_provider']);
        } else {
            // Sem turnos, o profissional volta a usar o horário padrão da clínica.
            $this->db->update(
                'user_settings',
                ['working_plan' => setting('company_working_plan')],
                ['id_users' => $shift['id_users_provider']],
            );
        }

        $this->session->set_flashdata('shift_success', lang('shift_deleted'));

        redirect('provider_shifts?provider=' . $shift['id_users_provider']);
    }

    /**
     * Confere a permissão; sem login manda para o login, sem permissão devolve 403.
     */
    private function allow(string $action): bool
    {
        if (can($action, PRIV_USERS)) {
            return true;
        }

        if (session('user_id')) {
            abort(403, 'Forbidden');
        } else {
            redirect('login');
        }

        return false;
    }
}
