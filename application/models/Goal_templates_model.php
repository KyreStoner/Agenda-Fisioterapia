<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Metas padrão do tratamento (escolhidas numa lista na avaliação).
 */
class Goal_templates_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
    ];

    /**
     * Todas as metas padrão, gerais primeiro e depois por área.
     */
    public function get_all(): array
    {
        $templates = $this->db->order_by('area IS NOT NULL', '', false)->order_by('area')->order_by('goal')
            ->get('goal_templates')
            ->result_array();

        foreach ($templates as &$template) {
            $this->cast($template);
        }

        return $templates;
    }

    public function find(int $id): array
    {
        $template = $this->db->get_where('goal_templates', ['id' => $id])->row_array();

        if (!$template) {
            throw new InvalidArgumentException(lang('goal_template_not_found'));
        }

        $this->cast($template);

        return $template;
    }

    /**
     * Cria ou atualiza uma meta padrão e devolve o ID.
     *
     * @param array $areas Ids de áreas válidos (catálogo clínico).
     *
     * @throws InvalidArgumentException Com a mensagem para o usuário.
     */
    public function save(array $template, array $areas): int
    {
        $data = [
            'area' => trim((string) ($template['area'] ?? '')) ?: null,
            'goal' => trim((string) ($template['goal'] ?? '')),
            'plan' => trim((string) ($template['plan'] ?? '')) ?: null,
            'update_datetime' => date('Y-m-d H:i:s'),
        ];

        if ($data['goal'] === '' || mb_strlen($data['goal']) > 500) {
            throw new InvalidArgumentException(lang('goal_template_goal_required'));
        }

        if ($data['area'] !== null && !in_array($data['area'], $areas, true)) {
            throw new InvalidArgumentException(lang('goal_template_invalid_area'));
        }

        if ($data['plan'] !== null && mb_strlen($data['plan']) > 2000) {
            throw new InvalidArgumentException(lang('clinical_record_text_too_long') . ' ' . lang('cr_goal_plan'));
        }

        if (!empty($template['id'])) {
            $this->find((int) $template['id']);
            $this->db->update('goal_templates', $data, ['id' => (int) $template['id']]);

            return (int) $template['id'];
        }

        $data['create_datetime'] = $data['update_datetime'];
        $this->db->insert('goal_templates', $data);

        return (int) $this->db->insert_id();
    }

    public function delete(int $id): void
    {
        $this->db->delete('goal_templates', ['id' => $id]);
    }
}
