<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tipos de registro do prontuário (tipos de evolução) e o que cada perfil pode fazer em cada tipo.
 *
 * O administrador define em Configurações → Prontuário: nome do tipo, ficha usada (application/config/clinical_forms.php)
 * e, por perfil, "ver" e "adicionar". Editar = adicionar (e só o autor edita o próprio registro). Adicionar inclui ver.
 *
 * A permissão geral "clinical_records" de cada perfil (tabela roles) é recalculada a partir desta tabela (sync_roles),
 * para as telas que só perguntam "este perfil vê o prontuário?" (botão em Pacientes, balão da agenda, menu).
 */
class Clinical_record_types_model extends EA_Model
{
    /**
     * Perfis que aparecem na tela de permissões, na ordem.
     */
    const ROLES = [DB_SLUG_PROVIDER, DB_SLUG_ADMIN, DB_SLUG_SECRETARY];

    const MAX_NAME_LENGTH = 100;

    protected array $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Tipos já lidos nesta requisição (as permissões são consultadas várias vezes por página).
     */
    private ?array $cache = null;

    /**
     * Todos os tipos, na ordem, com as permissões: [..., 'permissions' => [perfil => ['view' => bool, 'add' => bool]]].
     */
    public function get_all(bool $only_active = false): array
    {
        $this->cache ??= $this->load_all();

        return $only_active ? array_values(array_filter($this->cache, fn($type) => $type['active'])) : $this->cache;
    }

    private function load_all(): array
    {
        $types = $this->db->order_by('sort_order')->order_by('id')->get('clinical_record_types')->result_array();

        $permissions = [];

        foreach ($this->db->get('clinical_record_type_roles')->result_array() as $row) {
            $permissions[(int) $row['id_clinical_record_types']][$row['role_slug']] = [
                'view' => (bool) $row['can_view'],
                'add' => (bool) $row['can_add'],
            ];
        }

        foreach ($types as &$type) {
            $this->cast($type);

            $type['permissions'] = [];

            foreach (self::ROLES as $role) {
                $type['permissions'][$role] = $permissions[$type['id']][$role] ?? ['view' => false, 'add' => false];
            }
        }

        return $types;
    }

    /**
     * Tipo pelo id, ou null.
     */
    public function find(int $type_id): ?array
    {
        foreach ($this->get_all() as $type) {
            if ($type['id'] === $type_id) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Tipos que um perfil vê (ids).
     *
     * @return int[]
     */
    public function viewable_ids(?string $role_slug): array
    {
        $ids = [];

        foreach ($this->get_all() as $type) {
            if (!empty($type['permissions'][$role_slug]['view'])) {
                $ids[] = $type['id'];
            }
        }

        return $ids;
    }

    /**
     * Tipos ativos em que um perfil pode adicionar registros, na ordem.
     */
    public function addable(?string $role_slug): array
    {
        return array_values(
            array_filter($this->get_all(true), fn($type) => !empty($type['permissions'][$role_slug]['add'])),
        );
    }

    /**
     * Diz se o perfil pode adicionar (e editar os próprios) registros deste tipo. Tipo desativado não recebe registros
     * novos, mas quem pode adicionar continua editando os que já fez.
     */
    public function can_add(int $type_id, ?string $role_slug, bool $new_record = true): bool
    {
        $type = $this->find($type_id);

        if (!$type || ($new_record && !$type['active'])) {
            return false;
        }

        return !empty($type['permissions'][$role_slug]['add']);
    }

    /**
     * Tipo de um registro. Registros sem tipo (feitos antes dos tipos existirem) ficam com o primeiro tipo da mesma ficha.
     */
    public function type_id_for(array $record): ?int
    {
        if (!empty($record['id_clinical_record_types'])) {
            return (int) $record['id_clinical_record_types'];
        }

        foreach ($this->get_all() as $type) {
            if ($type['form'] === $record['type']) {
                return $type['id'];
            }
        }

        return null;
    }

    /**
     * Primeiro tipo ativo de uma ficha em que o perfil pode adicionar (para endereços antigos .../create/{paciente}/evolution).
     */
    public function first_addable_for_form(string $form, ?string $role_slug): ?array
    {
        foreach ($this->addable($role_slug) as $type) {
            if ($type['form'] === $form) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Cria um tipo. Começa liberado como os tipos iniciais: o profissional vê e adiciona, o administrador só vê.
     *
     * @throws InvalidArgumentException
     */
    public function create(string $name, string $form): int
    {
        $name = $this->clean_name($name);
        $this->validate_form($form);

        $now = date('Y-m-d H:i:s');

        $max = $this->db->select_max('sort_order')->get('clinical_record_types')->row_array();

        $this->db->insert('clinical_record_types', [
            'name' => $name,
            'form' => $form,
            'sort_order' => (int) ($max['sort_order'] ?? 0) + 1,
            'active' => 1,
            'create_datetime' => $now,
            'update_datetime' => $now,
        ]);

        $type_id = (int) $this->db->insert_id();

        $this->set_permissions($type_id, [
            DB_SLUG_PROVIDER => ['view' => true, 'add' => true],
            DB_SLUG_ADMIN => ['view' => true, 'add' => false],
            DB_SLUG_SECRETARY => ['view' => false, 'add' => false],
        ]);

        $this->sync_roles();

        return $type_id;
    }

    /**
     * Salva a tela inteira: nome, ficha, ordem, ativo e permissões de cada tipo.
     *
     * @param array $input [id => ['name' => ..., 'form' => ..., 'sort_order' => ..., 'active' => ..., 'permissions' =>
     *                     [perfil => ['view' => ..., 'add' => ...]]]]
     *
     * @throws InvalidArgumentException
     */
    public function save_all(array $input): void
    {
        $existing = array_column($this->get_all(), null, 'id');

        $clean = [];

        foreach ($input as $type_id => $type) {
            $type_id = (int) $type_id;

            if (!isset($existing[$type_id]) || !is_array($type)) {
                continue;
            }

            $form = (string) ($type['form'] ?? '');
            $this->validate_form($form);

            $permissions = [];

            foreach (self::ROLES as $role) {
                $add = !empty($type['permissions'][$role]['add']);

                $permissions[$role] = [
                    'view' => $add || !empty($type['permissions'][$role]['view']), // Quem adiciona também vê.
                    'add' => $add,
                ];
            }

            $clean[$type_id] = [
                'name' => $this->clean_name((string) ($type['name'] ?? '')),
                'form' => $form,
                'sort_order' => (int) ($type['sort_order'] ?? 0),
                'active' => !empty($type['active']) ? 1 : 0,
                'permissions' => $permissions,
            ];
        }

        $this->db->trans_start();

        foreach ($clean as $type_id => $type) {
            $this->db->update(
                'clinical_record_types',
                [
                    'name' => $type['name'],
                    'form' => $type['form'],
                    'sort_order' => $type['sort_order'],
                    'active' => $type['active'],
                    'update_datetime' => date('Y-m-d H:i:s'),
                ],
                ['id' => $type_id],
            );

            $this->set_permissions($type_id, $type['permissions']);
        }

        $this->cache = null;

        $this->sync_roles();

        $this->db->trans_complete();
    }

    /**
     * Apaga um tipo que ainda não tem registros (com registros, só dá para desativar).
     *
     * @throws InvalidArgumentException
     */
    public function delete(int $type_id): void
    {
        if ($this->db->get_where('clinical_records', ['id_clinical_record_types' => $type_id], 1)->num_rows()) {
            throw new InvalidArgumentException(lang('cr_type_in_use'));
        }

        $this->db->delete('clinical_record_types', ['id' => $type_id]);

        $this->cache = null;

        $this->sync_roles();
    }

    /**
     * Recalcula a permissão geral "clinical_records" de cada perfil: vê o prontuário se vê algum tipo; adiciona (e edita
     * os próprios registros) se adiciona em algum tipo.
     */
    public function sync_roles(): void
    {
        $types = $this->get_all();

        foreach (self::ROLES as $role) {
            $view = false;
            $add = false;

            foreach ($types as $type) {
                $view = $view || $type['permissions'][$role]['view'];
                $add = $add || ($type['active'] && $type['permissions'][$role]['add']);
            }

            $privileges = ($view ? PRIV_VIEW : 0) + ($add ? PRIV_ADD + PRIV_EDIT : 0);

            $this->db->update('roles', ['clinical_records' => $privileges], ['slug' => $role]);
        }
    }

    /**
     * Fichas que um tipo pode usar (application/config/clinical_forms.php).
     *
     * @return string[]
     */
    public function forms(): array
    {
        return Clinical_records_model::TYPES;
    }

    private function set_permissions(int $type_id, array $permissions): void
    {
        $this->cache = null;

        $this->db->delete('clinical_record_type_roles', ['id_clinical_record_types' => $type_id]);

        foreach (self::ROLES as $role) {
            $this->db->insert('clinical_record_type_roles', [
                'id_clinical_record_types' => $type_id,
                'role_slug' => $role,
                'can_view' => !empty($permissions[$role]['view']) ? 1 : 0,
                'can_add' => !empty($permissions[$role]['add']) ? 1 : 0,
            ]);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function clean_name(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException(lang('cr_type_name_required'));
        }

        return $name;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validate_form(string $form): void
    {
        if (!in_array($form, $this->forms(), true)) {
            throw new InvalidArgumentException(lang('cr_type_invalid_form'));
        }
    }
}
