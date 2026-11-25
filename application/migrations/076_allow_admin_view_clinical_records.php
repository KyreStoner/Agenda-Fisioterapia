<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: o administrador passa a ver o prontuário, só para leitura (pedido do usuário, que é o administrador do
 * sistema). Ele não cria nem edita registros, e cada acesso dele fica no histórico de acessos do prontuário, como o
 * de qualquer usuário. A recepcionista continua sem acesso ao conteúdo clínico.
 */
class Migration_Allow_admin_view_clinical_records extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if ($this->db->field_exists('clinical_records', 'roles')) {
            $this->db->update('roles', ['clinical_records' => PRIV_VIEW], ['slug' => DB_SLUG_ADMIN]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('clinical_records', 'roles')) {
            $this->db->update('roles', ['clinical_records' => 0], ['slug' => DB_SLUG_ADMIN]);
        }
    }
}
