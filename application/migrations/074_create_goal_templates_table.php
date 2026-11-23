<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: metas padrão do tratamento.
 *
 * A fisioterapeuta cadastra os tipos de meta que mais usa, por área do corpo (ou gerais), e na avaliação escolhe numa
 * lista em vez de digitar. "area" é o id de área de application/config/clinical_catalog.php (NULL = meta geral).
 */
class Migration_Create_goal_templates_table extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('goal_templates')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'area' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'goal' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'plan' => ['type' => 'TEXT', 'null' => true],
            'create_datetime' => ['type' => 'DATETIME', 'null' => true],
            'update_datetime' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('area');
        $this->dbforge->create_table('goal_templates', true, ['engine' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->dbforge->drop_table('goal_templates', true);
    }
}
