<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Fichas do prontuário
|--------------------------------------------------------------------------
|
| Campos da avaliação e da evolução, agrupados em seções. A tela e a validação são montadas a partir desta lista,
| então para mudar a ficha (ou criar a de outra especialidade no futuro) basta editar aqui e os textos em
| application/language/portuguese-br/translations_lang.php (chaves cr_section_* e cr_field_*).
|
| Tipos:
|  - text (uma linha), textarea (várias linhas), number (inteiro);
|  - pain_scale: escala de dor 0 a 10 (também guardada em coluna própria, para acompanhar a evolução da dor);
|  - body_map: bonequinho (frente, costas, lado direito e esquerdo) para marcar os locais da dor;
|  - region_tests: testes especiais e questionários das regiões marcadas no body_map (catálogo em
|    application/config/clinical_catalog.php);
|  - goals: metas por semana (semana, meta e como atingir);
|  - goals_check: na evolução, marca as metas da avaliação atingidas na sessão.
|
| Campo removido da ficha continua aparecendo nos registros antigos (seção "Outros registros"). Não reaproveite o
| nome de um campo antigo para outra coisa.
|
| Ficha definida com a fisioterapeuta da clínica, a partir do modelo usual (Resolução COFFITO nº 414/2012). O modelo
| completo do exame físico ainda vai ser enviado por ela.
|
*/

$config['clinical_forms'] = [
    'assessment' => [
        'anamnesis' => [
            'chief_complaint' => ['type' => 'textarea', 'required' => true],
            'history_present_illness' => ['type' => 'textarea'],
            'past_history' => ['type' => 'textarea'],
            'medications' => ['type' => 'textarea'],
            'exams' => ['type' => 'textarea'],
        ],
        'pain' => [
            'pain_scale' => ['type' => 'pain_scale'],
            'pain_regions' => ['type' => 'body_map'],
            'pain_characteristics' => ['type' => 'textarea'],
        ],
        'physical_exam' => [
            'inspection' => ['type' => 'textarea'],
            'palpation' => ['type' => 'textarea'],
            'range_of_motion' => ['type' => 'textarea'],
            'muscle_strength' => ['type' => 'textarea'],
            'region_tests' => ['type' => 'region_tests', 'regions_field' => 'pain_regions'],
            'special_tests' => ['type' => 'textarea'],
            'functional_assessment' => ['type' => 'textarea'],
        ],
        'diagnosis_plan' => [
            'physio_diagnosis' => ['type' => 'textarea', 'required' => true],
            'other_findings' => ['type' => 'textarea'],
            'goals' => ['type' => 'goals', 'required' => true],
            'planned_sessions' => ['type' => 'number'],
            'session_frequency' => ['type' => 'text'],
        ],
    ],
    'evolution' => [
        'session' => [
            'pain_scale' => ['type' => 'pain_scale'],
            'pain_regions' => ['type' => 'body_map'],
            'procedures' => ['type' => 'textarea', 'required' => true],
            'goals_achieved' => ['type' => 'goals_check'],
            'patient_response' => ['type' => 'textarea'],
            'occurrences' => ['type' => 'textarea'],
            'next_steps' => ['type' => 'textarea'],
        ],
    ],
];
