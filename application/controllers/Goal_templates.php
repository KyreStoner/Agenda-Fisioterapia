<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Metas padrão do tratamento: a fisioterapeuta cadastra os tipos de meta que mais usa (gerais ou por área do corpo) e
 * na avaliação escolhe numa lista. Acesso para quem cria registros no prontuário (profissional de saúde).
 */
class Goal_templates extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('goal_templates_model');
        $this->load->model('clinical_records_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('goal_templates')]);

        if (!$this->allow()) {
            return;
        }

        $templates = $this->goal_templates_model->get_all();
        $edit_id = (int) request('edit', 0);
        $form = $this->session->flashdata('goal_template_old') ?: null;

        if (!$form && $edit_id) {
            foreach ($templates as $template) {
                if ($template['id'] === $edit_id) {
                    $form = $template;
                }
            }
        }

        html_vars([
            'page_title' => lang('goal_templates'),
            'active_menu' => PRIV_CUSTOMERS,
            'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
            'templates' => $templates,
            'areas' => $this->clinical_records_model->catalog()['areas'],
            'form' => array_merge(['id' => null, 'area' => '', 'goal' => '', 'plan' => ''], $form ?? []),
            'success_message' => $this->session->flashdata('goal_template_success'),
            'error_message' => $this->session->flashdata('goal_template_error'),
        ]);

        $this->load->view('pages/goal_templates');
    }

    public function save(): void
    {
        method('post');

        if (!$this->allow()) {
            return;
        }

        $template = [
            'id' => (int) request('id') ?: null,
            'area' => request('area'),
            'goal' => request('goal'),
            'plan' => request('plan'),
        ];

        try {
            $this->goal_templates_model->save($template, array_keys($this->clinical_records_model->catalog()['areas']));
            $this->session->set_flashdata('goal_template_success', lang('goal_template_saved'));

            redirect('goal_templates');
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('goal_template_error', $e->getMessage());
            $this->session->set_flashdata('goal_template_old', $template);

            redirect('goal_templates' . ($template['id'] ? '?edit=' . $template['id'] : ''));
        }
    }

    public function delete(): void
    {
        method('post');

        if (!$this->allow()) {
            return;
        }

        $this->goal_templates_model->delete((int) request('id'));
        $this->session->set_flashdata('goal_template_success', lang('goal_template_deleted'));

        redirect('goal_templates');
    }

    /**
     * Só profissional de saúde (quem cria registros no prontuário).
     */
    private function allow(): bool
    {
        if (can('add', PRIV_CLINICAL_RECORDS)) {
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
