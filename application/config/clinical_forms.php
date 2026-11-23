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
| Tipos: text (uma linha), textarea (várias linhas), number (inteiro) e pain_scale (escala de dor 0 a 10, guardada
| também em coluna própria para acompanhar a evolução da dor).
|
| Modelo baseado na avaliação fisioterapêutica usual (anamnese, dor, exame físico, diagnóstico e plano), conforme o
| conteúdo mínimo de prontuário da Resolução COFFITO nº 414/2012.
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
            'pain_location' => ['type' => 'text'],
            'pain_characteristics' => ['type' => 'textarea'],
        ],
        'physical_exam' => [
            'inspection' => ['type' => 'textarea'],
            'palpation' => ['type' => 'textarea'],
            'range_of_motion' => ['type' => 'textarea'],
            'muscle_strength' => ['type' => 'textarea'],
            'special_tests' => ['type' => 'textarea'],
            'functional_assessment' => ['type' => 'textarea'],
        ],
        'diagnosis_plan' => [
            'physio_diagnosis' => ['type' => 'textarea', 'required' => true],
            'goals' => ['type' => 'textarea'],
            'treatment_plan' => ['type' => 'textarea', 'required' => true],
            'planned_sessions' => ['type' => 'number'],
            'session_frequency' => ['type' => 'text'],
        ],
    ],
    'evolution' => [
        'session' => [
            'pain_scale' => ['type' => 'pain_scale'],
            'procedures' => ['type' => 'textarea', 'required' => true],
            'patient_response' => ['type' => 'textarea'],
            'occurrences' => ['type' => 'textarea'],
            'next_steps' => ['type' => 'textarea'],
        ],
    ],
];
