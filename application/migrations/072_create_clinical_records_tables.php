<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: prontuário (avaliações e evoluções), histórico de alterações e registro de acessos.
 *
 * - Permissão nova "clinical_records" nos perfis: só o profissional de saúde (provider) acessa. Administrador e
 *   recepcionista ficam sem acesso ao conteúdo clínico (LGPD: dado de saúde é sensível).
 * - Registros do prontuário não são apagados (COFFITO): o paciente com prontuário não pode ser excluído (FK
 *   RESTRICT) e cada edição guarda a versão anterior.
 */
class Migration_Create_clinical_records_tables extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->field_exists('clinical_records', 'roles')) {
            $this->dbforge->add_column('roles', [
                'clinical_records' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 0],
            ]);

            $this->db->update('roles', ['clinical_records' => 0]);

            // Ver, criar e editar (sem excluir).
            $this->db->update('roles', ['clinical_records' => PRIV_VIEW + PRIV_ADD + PRIV_EDIT], ['slug' => DB_SLUG_PROVIDER]);
        }

        $users = $this->db->dbprefix('users');
        $appointments = $this->db->dbprefix('appointments');

        if (!$this->db->table_exists('clinical_records')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'type' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
                'id_users_customer' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'id_users_author' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'author_name' => ['type' => 'VARCHAR', 'constraint' => 256, 'null' => false],
                'id_appointments' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'record_date' => ['type' => 'DATETIME', 'null' => false],
                'pain_scale' => ['type' => 'TINYINT', 'constraint' => 2, 'null' => true],
                'content' => ['type' => 'LONGTEXT', 'null' => false],
                'create_datetime' => ['type' => 'DATETIME', 'null' => true],
                'update_datetime' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('clinical_records', true, ['engine' => 'InnoDB']);

            $table = $this->db->dbprefix('clinical_records');
            $this->db->query(
                "ALTER TABLE `$table`
                    ADD CONSTRAINT `clinical_records_users_customer` FOREIGN KEY (`id_users_customer`)
                        REFERENCES `$users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                    ADD CONSTRAINT `clinical_records_users_author` FOREIGN KEY (`id_users_author`)
                        REFERENCES `$users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    ADD CONSTRAINT `clinical_records_appointments` FOREIGN KEY (`id_appointments`)
                        REFERENCES `$appointments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE",
            );
        }

        if (!$this->db->table_exists('clinical_record_versions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'id_clinical_records' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'record_date' => ['type' => 'DATETIME', 'null' => false],
                'pain_scale' => ['type' => 'TINYINT', 'constraint' => 2, 'null' => true],
                'content' => ['type' => 'LONGTEXT', 'null' => false],
                'id_users' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'user_name' => ['type' => 'VARCHAR', 'constraint' => 256, 'null' => false],
                'create_datetime' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_clinical_records');
            $this->dbforge->create_table('clinical_record_versions', true, ['engine' => 'InnoDB']);

            $table = $this->db->dbprefix('clinical_record_versions');
            $records = $this->db->dbprefix('clinical_records');
            $this->db->query(
                "ALTER TABLE `$table` ADD CONSTRAINT `clinical_record_versions_records` FOREIGN KEY (`id_clinical_records`)
                    REFERENCES `$records` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE",
            );
        }

        if (!$this->db->table_exists('clinical_access_log')) {
            // Sem chaves estrangeiras de propósito: o registro de acessos fica mesmo se o usuário for removido.
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'id_users' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'user_name' => ['type' => 'VARCHAR', 'constraint' => 256, 'null' => false],
                'id_users_customer' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'id_clinical_records' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'action' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'create_datetime' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('clinical_access_log', true, ['engine' => 'InnoDB']);
        }
    }

    public function down(): void
    {
        $this->dbforge->drop_table('clinical_access_log', true);
        $this->dbforge->drop_table('clinical_record_versions', true);
        $this->dbforge->drop_table('clinical_records', true);

        if ($this->db->field_exists('clinical_records', 'roles')) {
            $this->dbforge->drop_column('roles', 'clinical_records');
        }
    }
}
