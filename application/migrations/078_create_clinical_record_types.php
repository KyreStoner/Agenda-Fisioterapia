<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: tipos de registro do prontuário (tipos de evolução) com permissão por perfil, pedido do usuário.
 *
 *  - clinical_record_types: cada tipo tem nome (o que aparece no prontuário: "Avaliação inicial", "Reavaliação"...) e a
 *    ficha que usa (assessment ou evolution, de application/config/clinical_forms.php). Tipo com registros não é
 *    apagado, só desativado.
 *  - clinical_record_type_roles: o que cada perfil (admin, provider, secretary) pode fazer em cada tipo: ver e/ou
 *    adicionar. Editar = adicionar, e só o autor edita o próprio registro.
 *  - clinical_records.id_clinical_record_types: tipo de cada registro. A coluna "type" continua dizendo a ficha usada
 *    quando o registro foi feito.
 *
 * Começa com dois tipos, para os registros que já existem: "Avaliação inicial" (ficha de avaliação) e "Evolução de
 * seguimento" (ficha de evolução), com as permissões que cada perfil já tinha no prontuário (migrações 072 e 076).
 */
class Migration_Create_clinical_record_types extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('clinical_record_types')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'form' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
                'sort_order' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'create_datetime' => ['type' => 'DATETIME', 'null' => true],
                'update_datetime' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('clinical_record_types', true, ['engine' => 'InnoDB']);

            $now = date('Y-m-d H:i:s');

            $this->db->insert('clinical_record_types', [
                'name' => 'Avaliação inicial',
                'form' => 'assessment',
                'sort_order' => 1,
                'active' => 1,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]);

            $this->db->insert('clinical_record_types', [
                'name' => 'Evolução de seguimento',
                'form' => 'evolution',
                'sort_order' => 2,
                'active' => 1,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]);
        }

        if (!$this->db->table_exists('clinical_record_type_roles')) {
            $this->dbforge->add_field([
                'id_clinical_record_types' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'role_slug' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
                'can_view' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
                'can_add' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            ]);
            $this->dbforge->add_key(['id_clinical_record_types', 'role_slug'], true);
            $this->dbforge->create_table('clinical_record_type_roles', true, ['engine' => 'InnoDB']);

            $table = $this->db->dbprefix('clinical_record_type_roles');
            $types = $this->db->dbprefix('clinical_record_types');

            $this->db->query(
                "ALTER TABLE `$table` ADD CONSTRAINT `clinical_record_type_roles_types` FOREIGN KEY (`id_clinical_record_types`)
                    REFERENCES `$types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            );

            // Cada perfil continua podendo o que já podia no prontuário.
            foreach ($this->db->get('roles')->result_array() as $role) {
                if (!in_array($role['slug'], [DB_SLUG_ADMIN, DB_SLUG_PROVIDER, DB_SLUG_SECRETARY], true)) {
                    continue;
                }

                $privileges = (int) ($role['clinical_records'] ?? 0);

                foreach ($this->db->get('clinical_record_types')->result_array() as $type) {
                    $this->db->insert('clinical_record_type_roles', [
                        'id_clinical_record_types' => $type['id'],
                        'role_slug' => $role['slug'],
                        'can_view' => $privileges & PRIV_VIEW ? 1 : 0,
                        'can_add' => $privileges & PRIV_ADD ? 1 : 0,
                    ]);
                }
            }
        }

        if (!$this->db->field_exists('id_clinical_record_types', 'clinical_records')) {
            $this->dbforge->add_column('clinical_records', [
                'id_clinical_record_types' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'type'],
            ]);

            $records = $this->db->dbprefix('clinical_records');
            $types = $this->db->dbprefix('clinical_record_types');

            $this->db->query(
                "ALTER TABLE `$records` ADD CONSTRAINT `clinical_records_types` FOREIGN KEY (`id_clinical_record_types`)
                    REFERENCES `$types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE",
            );

            // Registros que já existem: avaliação → "Avaliação inicial", evolução → "Evolução de seguimento".
            $first_type_by_form = [];

            foreach ($this->db->order_by('sort_order, id')->get('clinical_record_types')->result_array() as $type) {
                $first_type_by_form[$type['form']] ??= (int) $type['id'];
            }

            foreach ($first_type_by_form as $form => $type_id) {
                $this->db->update(
                    'clinical_records',
                    ['id_clinical_record_types' => $type_id],
                    ['type' => $form, 'id_clinical_record_types' => null],
                );
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_clinical_record_types', 'clinical_records')) {
            $records = $this->db->dbprefix('clinical_records');

            $this->db->query("ALTER TABLE `$records` DROP FOREIGN KEY `clinical_records_types`");

            $this->dbforge->drop_column('clinical_records', 'id_clinical_record_types');
        }

        $this->dbforge->drop_table('clinical_record_type_roles', true);
        $this->dbforge->drop_table('clinical_record_types', true);
    }
}
