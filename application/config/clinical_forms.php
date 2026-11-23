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
|  - goals_check: na evolução, marca as metas da avaliação atingidas na sessão;
|  - choice: opções rápidas (rótulos em cr_opt_<campo>_<opção>);
|  - measure: número com unidade, mínimo, máximo e casas decimais (step);
|  - blood_pressure: pressão arterial (sistólica/diastólica);
|  - bmi: IMC calculado a partir do peso e da altura;
|  - neuro_screen: dermátomos e miótomos dos níveis ligados às regiões marcadas no body_map.
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
            'surgeries' => ['type' => 'textarea'],
            'medications' => ['type' => 'textarea'],
            'exams' => ['type' => 'textarea'],
        ],
        'lifestyle' => [
            'physical_activity' => ['type' => 'choice', 'options' => ['yes', 'no']],
            'physical_activity_details' => ['type' => 'text'],
            'smoking' => ['type' => 'choice', 'options' => ['never', 'former', 'current']],
            'alcohol' => ['type' => 'choice', 'options' => ['no', 'socially', 'frequently']],
            'cancer_history' => ['type' => 'choice', 'options' => ['none', 'personal', 'family', 'both']],
            'cancer_details' => ['type' => 'text'],
            'treatments' => ['type' => 'textarea'],
        ],
        'perception' => [
            'beliefs' => ['type' => 'textarea'],
            'sleep' => ['type' => 'choice', 'options' => ['good', 'fair', 'poor']],
            'sleep_details' => ['type' => 'text'],
            'mood_stress' => ['type' => 'textarea'],
            'psychological_support' => ['type' => 'textarea'],
        ],
        'pain' => [
            'pain_scale' => ['type' => 'pain_scale'],
            'pain_regions' => ['type' => 'body_map'],
            'pain_characteristics' => ['type' => 'textarea'],
        ],
        'vital_signs' => [
            'blood_pressure' => ['type' => 'blood_pressure'],
            'heart_rate' => ['type' => 'measure', 'unit' => 'bpm', 'min' => 20, 'max' => 250, 'step' => 1],
            'respiratory_rate' => ['type' => 'measure', 'unit' => 'irpm', 'min' => 4, 'max' => 80, 'step' => 1],
            'oxygen_saturation' => ['type' => 'measure', 'unit' => '%', 'min' => 50, 'max' => 100, 'step' => 1],
            'temperature' => ['type' => 'measure', 'unit' => '°C', 'min' => 30, 'max' => 45, 'step' => 0.1],
            'weight' => ['type' => 'measure', 'unit' => 'kg', 'min' => 1, 'max' => 400, 'step' => 0.1],
            'height' => ['type' => 'measure', 'unit' => 'm', 'min' => 0.3, 'max' => 2.5, 'step' => 0.01],
            'bmi' => ['type' => 'bmi', 'weight_field' => 'weight', 'height_field' => 'height'],
        ],
        'physical_exam' => [
            'inspection' => ['type' => 'textarea'],
            'palpation' => ['type' => 'textarea'],
            'range_of_motion' => ['type' => 'textarea'],
            'muscle_strength' => ['type' => 'textarea'],
            'neuro_screen' => ['type' => 'neuro_screen', 'regions_field' => 'pain_regions'],
            'region_tests' => ['type' => 'region_tests', 'regions_field' => 'pain_regions'],
            'special_tests' => ['type' => 'textarea'],
            'functional_assessment' => ['type' => 'textarea'],
        ],
        'diagnosis_plan' => [
            'physio_diagnosis' => ['type' => 'textarea', 'required' => true],
            'other_findings' => ['type' => 'textarea'],
            'goals' => ['type' => 'goals', 'required' => true, 'regions_field' => 'pain_regions'],
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
