<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configurações → Prontuário: tipos de registro (tipos de evolução) e o que cada perfil pode fazer em cada um (ver,
 * adicionar). Só o administrador (permissão de configurações do sistema).
 *
 * Tela renderizada no servidor, com formulários POST e csrf_token, sem depender de JS compilado.
 */
class Clinical_record_types extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('clinical_records_model');
        $this->load->model('clinical_record_types_model');

        $this->load->library('accounts');
    }

    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('clinical_record_types')]);

        if (!$this->allow('view')) {
            return;
        }

        html_vars([
            'page_title' => lang('cr_types_title'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
            'types' => $this->clinical_record_types_model->get_all(),
            'record_counts' => $this->record_counts(),
            'forms' => $this->clinical_record_types_model->forms(),
            'roles' => Clinical_record_types_model::ROLES,
            'can_edit' => can('edit', PRIV_SYSTEM_SETTINGS),
            'success_message' => $this->session->flashdata('cr_types_success'),
            'error_message' => $this->session->flashdata('cr_types_error'),
        ]);

        $this->load->view('pages/clinical_record_types');
    }

    /**
     * Salva a lista inteira (nomes, fichas, ordem, ativo e permissões).
     */
    public function save(): void
    {
        method('post');

        if (!$this->allow('edit')) {
            return;
        }

        $this->run(
            fn() => $this->clinical_record_types_model->save_all((array) request('types', [])),
            lang('cr_type_saved'),
        );
    }

    /**
     * Cria um tipo novo.
     */
    public function create(): void
    {
        method('post');

        if (!$this->allow('edit')) {
            return;
        }

        $this->run(
            fn() => $this->clinical_record_types_model->create((string) request('name'), (string) request('form')),
            lang('cr_type_created'),
        );
    }

    /**
     * Exclui um tipo sem registros.
     */
    public function delete(): void
    {
        method('post');

        if (!$this->allow('edit')) {
            return;
        }

        $this->run(fn() => $this->clinical_record_types_model->delete((int) request('id')), lang('cr_type_deleted'));
    }

    /**
     * Executa a alteração e volta para a tela com a mensagem de sucesso ou de erro.
     */
    private function run(callable $action, string $success_message): void
    {
        try {
            $action();

            $this->session->set_flashdata('cr_types_success', $success_message);
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('cr_types_error', $e->getMessage());
        }

        redirect('clinical_record_types');
    }

    /**
     * Quantos registros cada tipo tem (tipo com registros não pode ser excluído).
     */
    private function record_counts(): array
    {
        $counts = [];

        $rows = $this->db
            ->select('id_clinical_record_types, COUNT(*) AS total')
            ->group_by('id_clinical_record_types')
            ->get('clinical_records')
            ->result_array();

        foreach ($rows as $row) {
            $counts[(int) $row['id_clinical_record_types']] = (int) $row['total'];
        }

        return $counts;
    }

    private function allow(string $action): bool
    {
        if (can($action, PRIV_SYSTEM_SETTINGS)) {
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
