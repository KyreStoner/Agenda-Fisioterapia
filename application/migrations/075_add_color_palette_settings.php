<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração: paletas de cores.
 *
 *  - Configuração "color_palette": paleta da clínica (Configurações → Geral), vale para o sistema todo. Quem já tinha
 *    uma cor personalizada continua com ela ("custom"); os demais ficam com o verde original ("default").
 *  - Coluna user_settings.color_palette: paleta que o usuário escolheu para si em Conta (NULL = segue a da clínica).
 *
 * As paletas ficam em application/config/color_palettes.php.
 */
class Migration_Add_color_palette_settings extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->get_where('settings', ['name' => 'color_palette'])->num_rows()) {
            $company_color = $this->db->get_where('settings', ['name' => 'company_color'])->row_array();

            $has_custom_color =
                !empty($company_color['value']) &&
                preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $company_color['value']) &&
                strtolower($company_color['value']) !== '#ffffff';

            $this->db->insert('settings', [
                'name' => 'color_palette',
                'value' => $has_custom_color ? 'custom' : 'default',
            ]);
        }

        if (!$this->db->field_exists('color_palette', 'user_settings')) {
            $this->dbforge->add_column('user_settings', [
                'color_palette' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'null' => true,
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->db->delete('settings', ['name' => 'color_palette']);

        if ($this->db->field_exists('color_palette', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'color_palette');
        }
    }
}
