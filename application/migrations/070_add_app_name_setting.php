<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: configuração "Nome do sistema" (app_name).
 *
 * Guarda o nome do aplicativo em um único lugar, editável em Configurações → Geral.
 * O valor inicial vem de application/config/app.php.
 */
class Migration_Add_app_name_setting extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->get_where('settings', ['name' => 'app_name'])->num_rows()) {
            $this->db->insert('settings', [
                'name' => 'app_name',
                'value' => config('app_name'),
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->db->delete('settings', ['name' => 'app_name']);
    }
}
