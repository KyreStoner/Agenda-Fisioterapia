<?php
/**
 * Prontuários FICTÍCIOS de demonstração (Codespaces): três pacientes de exemplo com avaliação completa (mapa da dor,
 * testes especiais, questionários, CBDF, metas) e evoluções com metas atingidas.
 *
 * Uso: php .devcontainer/demo-records.php
 *
 * Pode rodar de novo: apaga e recria só os prontuários desses pacientes de exemplo. Nunca use com dados reais.
 */

$db = new mysqli(
    'localhost',
    getenv('DB_USER') ?: 'agenda',
    getenv('DB_PASS') ?: 'agenda-dev-local', // Apenas para o ambiente local de demonstração.
    getenv('DB_NAME') ?: 'agenda_fisio',
);
$db->set_charset('utf8mb4');

$provider = $db
    ->query("SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name FROM ea_users u
             JOIN ea_roles r ON r.id = u.id_roles WHERE r.slug = 'provider' ORDER BY u.id LIMIT 1")
    ->fetch_assoc();

if (!$provider) {
    fwrite(STDERR, "Nenhum profissional encontrado. Rode antes o demo-data.sh.\n");
    exit(1);
}

$day = fn(int $days_ago, string $time = '09:00') => date('Y-m-d', strtotime("-{$days_ago} days")) . " {$time}:00";

$patients = [
    'paciente1@exemplo.com.br' => [
        'assessment' => [
            'date' => $day(21),
            'content' => [
                'chief_complaint' => 'Dor lombar há 3 meses, que piora ao ficar sentado no trabalho e melhora ao caminhar.',
                'history_present_illness' => 'Início gradual depois de mudar de função (trabalho sentado 8 h por dia). Há 2 semanas a dor passou a descer pela parte de trás da coxa direita, até o joelho.',
                'past_history' => 'Nega cirurgias. Episódio de dor lombar leve há 2 anos, que melhorou sozinho.',
                'medications' => 'Analgésico simples quando a dor aumenta.',
                'exams' => 'Ressonância da coluna lombar: protrusão discal L4-L5, sem compressão importante (laudo trazido pelo paciente).',
                'pain_scale' => 7,
                'pain_regions' => ['coluna_lombar', 'coxa_d'],
                'pain_characteristics' => 'Dor em queimação na lombar, com irradiação para a coxa direita ao final do dia. Piora sentado mais de 30 minutos e ao levantar peso; melhora deitado e caminhando.',
                'inspection' => 'Retificação da lordose lombar. Marcha sem alterações.',
                'palpation' => 'Tensão e dor à palpação da musculatura paravertebral lombar, mais à direita.',
                'range_of_motion' => 'Flexão de tronco limitada (dedos a 25 cm do chão), com dor no final do movimento. Extensão sem dor.',
                'muscle_strength' => 'Membros inferiores grau 5. Resistência reduzida dos estabilizadores do tronco.',
                'region_tests' => [
                    'lombar' => [
                        'tests' => ['lasegue' => 'positive', 'slump' => 'positive', 'schober' => 'negative', 'instabilidade_prono' => 'negative'],
                        'questionnaires' => ['oswestry' => '38%', 'roland_morris' => '12/24'],
                    ],
                    'quadril_d' => ['tests' => ['faber' => 'negative', 'thomas' => 'positive']],
                ],
                'special_tests' => 'Lasègue positivo a 50° à direita.',
                'functional_assessment' => 'Dificuldade para ficar sentado mais de 30 minutos e para calçar meias.',
                'physio_diagnosis' => '(Exemplo fictício) Disfunção do movimento da coluna lombar com irradiação para o membro inferior direito. Preencher com o código da CBDF.',
                'other_findings' => 'Encurtamento de iliopsoas à direita (Thomas positivo). Postura sentada prolongada no trabalho.',
                'goals' => [
                    ['id' => 'carlos01', 'week' => 1, 'goal' => 'Reduzir a dor para 5 ou menos', 'plan' => 'Orientações de postura, pausas ativas e exercícios de mobilidade lombar.'],
                    ['id' => 'carlos02', 'week' => 2, 'goal' => 'Ficar sentado 45 minutos sem dor', 'plan' => 'Mobilização neural, alongamento de iliopsoas e ajuste do posto de trabalho.'],
                    ['id' => 'carlos03', 'week' => 4, 'goal' => 'Caminhar 30 minutos sem dor', 'plan' => 'Fortalecimento de core e glúteos, progressão de caminhada.'],
                    ['id' => 'carlos04', 'week' => 6, 'goal' => 'Voltar a jogar futebol no fim de semana', 'plan' => 'Exercícios funcionais e de agilidade, retorno gradual ao esporte.'],
                ],
                'planned_sessions' => 12,
                'session_frequency' => '2 vezes por semana',
            ],
        ],
        'evolutions' => [
            ['date' => $day(14), 'content' => [
                'pain_scale' => 5, 'pain_regions' => ['coluna_lombar'],
                'procedures' => 'Mobilização lombar, mobilização neural do ciático, alongamento de iliopsoas e orientações de pausas no trabalho.',
                'goals_achieved' => ['carlos01'],
                'patient_response' => 'Relata que a dor não desce mais para a coxa. Saiu da sessão com dor 3.',
                'occurrences' => '', 'next_steps' => 'Manter exercícios de mobilidade em casa, 2 vezes ao dia.',
            ]],
            ['date' => $day(7), 'content' => [
                'pain_scale' => 3, 'pain_regions' => ['coluna_lombar'],
                'procedures' => 'Fortalecimento de core (prancha, ponte, bird-dog), alongamentos e progressão de caminhada.',
                'goals_achieved' => ['carlos02'],
                'patient_response' => 'Consegue trabalhar sentado com pausas a cada 50 minutos sem dor.',
                'occurrences' => 'Leve dor muscular após a sessão anterior (esperada).', 'next_steps' => 'Caminhar 20 minutos por dia.',
            ]],
        ],
    ],

    'paciente2@exemplo.com.br' => [
        'assessment' => [
            'date' => $day(18, '10:00'),
            'content' => [
                'chief_complaint' => 'Dor no ombro direito ao levantar o braço, há 6 semanas.',
                'history_present_illness' => 'Começou depois de pintar a casa (trabalho com os braços acima da cabeça por vários dias). Dor à noite ao deitar sobre o ombro.',
                'past_history' => 'Hipotireoidismo controlado.',
                'medications' => 'Levotiroxina.',
                'exams' => 'Ultrassom do ombro direito: tendinopatia do supraespinal, sem rotura.',
                'pain_scale' => 6,
                'pain_regions' => ['ombro_d', 'braco_d'],
                'pain_characteristics' => 'Dor em pontada na frente e na lateral do ombro, que irradia para o braço. Piora ao elevar o braço acima de 90° e à noite.',
                'inspection' => 'Ombro direito levemente elevado e protraído.',
                'palpation' => 'Dor à palpação do tubérculo maior do úmero.',
                'range_of_motion' => 'Flexão ativa 120° com arco doloroso entre 70° e 110°. Rotação interna limitada.',
                'muscle_strength' => 'Abdução e rotação externa grau 4 com dor.',
                'region_tests' => [
                    'ombro_d' => [
                        'tests' => ['neer' => 'positive', 'hawkins' => 'positive', 'jobe' => 'positive', 'patte' => 'negative', 'speed' => 'negative', 'apreensao' => 'negative'],
                        'questionnaires' => ['spadi' => '52', 'dash' => '41'],
                    ],
                ],
                'special_tests' => '',
                'functional_assessment' => 'Dificuldade para pentear o cabelo, prender o sutiã e alcançar prateleiras altas.',
                'physio_diagnosis' => '(Exemplo fictício) Disfunção do movimento do ombro direito por tendinopatia do manguito rotador. Preencher com o código da CBDF.',
                'other_findings' => 'Discinesia escapular à direita.',
                'goals' => [
                    ['id' => 'mariana1', 'week' => 1, 'goal' => 'Dormir a noite toda sem acordar com dor', 'plan' => 'Orientação de posição para dormir, crioterapia e exercícios pendulares.'],
                    ['id' => 'mariana2', 'week' => 2, 'goal' => 'Elevar o braço até 150° sem dor', 'plan' => 'Mobilização glenoumeral e exercícios ativo-assistidos.'],
                    ['id' => 'mariana3', 'week' => 3, 'goal' => 'Pentear o cabelo sem dor', 'plan' => 'Fortalecimento de manguito rotador e estabilizadores da escápula.'],
                    ['id' => 'mariana4', 'week' => 6, 'goal' => 'Voltar à academia (musculação leve)', 'plan' => 'Progressão de carga e exercícios funcionais acima da cabeça.'],
                ],
                'planned_sessions' => 10,
                'session_frequency' => '2 vezes por semana',
            ],
        ],
        'evolutions' => [
            ['date' => $day(11, '10:00'), 'content' => [
                'pain_scale' => 4, 'pain_regions' => ['ombro_d'],
                'procedures' => 'Mobilização glenoumeral, exercícios pendulares e fortalecimento isométrico de manguito rotador.',
                'goals_achieved' => ['mariana1'],
                'patient_response' => 'Dorme a noite toda há 4 dias.',
                'occurrences' => '', 'next_steps' => 'Exercícios com faixa elástica leve em casa.',
            ]],
            ['date' => $day(4, '10:00'), 'content' => [
                'pain_scale' => 3, 'pain_regions' => ['ombro_d'],
                'procedures' => 'Fortalecimento de rotadores externos com faixa elástica, estabilização escapular e alongamento posterior do ombro.',
                'goals_achieved' => ['mariana2', 'mariana3'],
                'patient_response' => 'Elevação ativa a 155° sem dor. Penteia o cabelo sem dificuldade.',
                'occurrences' => '', 'next_steps' => 'Iniciar exercícios com halter leve.',
            ]],
        ],
    ],

    'paciente4@exemplo.com.br' => [
        'assessment' => [
            'date' => $day(10, '14:00'),
            'content' => [
                'chief_complaint' => 'Dor e sensação de travamento no joelho esquerdo depois de uma torção jogando vôlei.',
                'history_present_illness' => 'Entorse em rotação há 4 semanas. Inchaço nos primeiros dias. Desde então, dor ao agachar e às vezes o joelho "trava".',
                'past_history' => 'Nega lesões anteriores no joelho.',
                'medications' => 'Nenhum.',
                'exams' => 'Ressonância do joelho esquerdo: lesão do corno posterior do menisco medial (laudo trazido pela paciente).',
                'pain_scale' => 5,
                'pain_regions' => ['joelho_e'],
                'pain_characteristics' => 'Dor na parte de dentro do joelho ao agachar, girar o corpo e descer escadas.',
                'inspection' => 'Edema leve no joelho esquerdo. Marcha com leve claudicação.',
                'palpation' => 'Dor à palpação da interlinha articular medial.',
                'range_of_motion' => 'Flexão 115° com dor no final; extensão completa.',
                'muscle_strength' => 'Quadríceps esquerdo grau 4.',
                'region_tests' => [
                    'joelho_e' => [
                        'tests' => ['lachman' => 'negative', 'gaveta_anterior' => 'negative', 'mcmurray' => 'positive', 'apley' => 'positive', 'estresse_valgo_joelho' => 'negative'],
                        'questionnaires' => ['lysholm' => '64', 'koos' => '58'],
                    ],
                ],
                'special_tests' => '',
                'functional_assessment' => 'Dificuldade para agachar e descer escadas.',
                'physio_diagnosis' => '(Exemplo fictício) Disfunção do movimento do joelho esquerdo por lesão meniscal medial. Preencher com o código da CBDF.',
                'other_findings' => 'Fraqueza de glúteo médio à esquerda.',
                'goals' => [
                    ['id' => 'beatriz1', 'week' => 1, 'goal' => 'Reduzir o inchaço e andar sem mancar', 'plan' => 'Crioterapia, drenagem e treino de marcha.'],
                    ['id' => 'beatriz2', 'week' => 3, 'goal' => 'Descer escadas sem dor', 'plan' => 'Fortalecimento de quadríceps e glúteo médio, treino excêntrico.'],
                    ['id' => 'beatriz3', 'week' => 6, 'goal' => 'Agachar até 90° sem dor', 'plan' => 'Exercícios em cadeia fechada com progressão de amplitude.'],
                    ['id' => 'beatriz4', 'week' => 10, 'goal' => 'Voltar a jogar vôlei', 'plan' => 'Treino de salto, aterrissagem e mudança de direção.'],
                ],
                'planned_sessions' => 16,
                'session_frequency' => '2 vezes por semana',
            ],
        ],
        'evolutions' => [
            ['date' => $day(3, '14:00'), 'content' => [
                'pain_scale' => 4, 'pain_regions' => ['joelho_e'],
                'procedures' => 'Crioterapia, exercícios isométricos de quadríceps, ponte e treino de marcha.',
                'goals_achieved' => ['beatriz1'],
                'patient_response' => 'Anda sem mancar. Edema bem menor.',
                'occurrences' => '', 'next_steps' => 'Iniciar fortalecimento em cadeia fechada.',
            ]],
        ],
    ],
];

// Campos da anamnese ampliada, sinais vitais e exame neurológico (avaliação de cada paciente de exemplo).
$bmi = fn(float $weight, float $height) => round($weight / ($height * $height), 1);

$extra = [
    'paciente1@exemplo.com.br' => [
        'surgeries' => 'Nenhuma.',
        'physical_activity' => 'no', 'physical_activity_details' => 'Parou de jogar futebol há 3 meses por causa da dor.',
        'smoking' => 'never', 'alcohol' => 'socially', 'cancer_history' => 'family',
        'cancer_details' => 'Pai com câncer de próstata (exemplo fictício).',
        'treatments' => 'Fez 5 sessões de acupuntura há 1 ano, com melhora parcial.',
        'beliefs' => 'Acha que a coluna está "desgastada" e tem medo de piorar ao se movimentar.',
        'sleep' => 'fair', 'sleep_details' => 'Dorme 6 horas; acorda com dor ao virar na cama.',
        'mood_stress' => 'Estresse alto no trabalho, prazos apertados.',
        'psychological_support' => 'Não faz.',
        'blood_pressure' => ['sys' => 128, 'dia' => 82], 'heart_rate' => 76, 'respiratory_rate' => 16,
        'oxygen_saturation' => 98, 'temperature' => 36.4, 'weight' => 86, 'height' => 1.78, 'bmi' => $bmi(86, 1.78),
        'neuro_screen' => [
            'L4' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 5, 'myo_e' => 5],
            'L5' => ['derm_d' => 'decreased', 'derm_e' => 'normal', 'myo_d' => 4, 'myo_e' => 5],
            'S1' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 5, 'myo_e' => 5],
        ],
        'answers' => ['lombar', 'exemplo_demo', [1, 0, 1, 1]],
    ],
    'paciente2@exemplo.com.br' => [
        'surgeries' => 'Cesárea em 2019.',
        'physical_activity' => 'yes', 'physical_activity_details' => 'Musculação 3 vezes por semana (parou há 1 mês).',
        'smoking' => 'never', 'alcohol' => 'no', 'cancer_history' => 'none', 'cancer_details' => '',
        'treatments' => 'Nenhum acompanhamento anterior para o ombro.',
        'beliefs' => 'Espera voltar à academia logo; acredita que precisa de repouso total.',
        'sleep' => 'poor', 'sleep_details' => 'Acorda 2 a 3 vezes por noite com dor ao deitar sobre o ombro.',
        'mood_stress' => 'Ansiosa por não conseguir treinar.',
        'psychological_support' => 'Faz terapia quinzenal há 1 ano.',
        'blood_pressure' => ['sys' => 118, 'dia' => 76], 'heart_rate' => 72, 'respiratory_rate' => 14,
        'oxygen_saturation' => 99, 'temperature' => 36.6, 'weight' => 62, 'height' => 1.65, 'bmi' => $bmi(62, 1.65),
        'neuro_screen' => [
            'C5' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 4, 'myo_e' => 5],
            'C6' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 5, 'myo_e' => 5],
            'C7' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 5, 'myo_e' => 5],
        ],
        'answers' => ['ombro_d', 'exemplo_demo', [1, 0, 2, 1]],
    ],
    'paciente4@exemplo.com.br' => [
        'surgeries' => 'Nenhuma.',
        'physical_activity' => 'yes', 'physical_activity_details' => 'Vôlei 2 vezes por semana.',
        'smoking' => 'never', 'alcohol' => 'socially', 'cancer_history' => 'none', 'cancer_details' => '',
        'treatments' => 'Usou joelheira por conta própria nas primeiras semanas.',
        'beliefs' => 'Tem medo de que o joelho "trave" de novo durante o jogo.',
        'sleep' => 'good', 'sleep_details' => 'Dorme 8 horas, sem dor à noite.',
        'mood_stress' => 'Tranquila; quer voltar ao time.',
        'psychological_support' => 'Não faz.',
        'blood_pressure' => ['sys' => 112, 'dia' => 70], 'heart_rate' => 68, 'respiratory_rate' => 14,
        'oxygen_saturation' => 99, 'temperature' => 36.5, 'weight' => 58, 'height' => 1.70, 'bmi' => $bmi(58, 1.70),
        'neuro_screen' => [
            'L3' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 5, 'myo_e' => 4],
            'L4' => ['derm_d' => 'normal', 'derm_e' => 'normal', 'myo_d' => 5, 'myo_e' => 5],
        ],
        'answers' => ['joelho_e', 'exemplo_demo', [1, 1, 0, 0]],
    ],
];

foreach ($extra as $email => $fields) {
    if (!isset($patients[$email])) {
        continue;
    }

    [$block, $questionnaire, $answers] = $fields['answers'];
    unset($fields['answers']);

    $content = &$patients[$email]['assessment']['content'];
    $content = array_merge($content, $fields);

    // Questionário de exemplo respondido no sistema (a pontuação é a soma dos pontos das respostas).
    $content['region_tests'][$block]['answers'][$questionnaire] = $answers;
    $content['region_tests'][$block]['questionnaires'][$questionnaire] = array_sum($answers) . '/8';
    unset($content);
}

// Metas padrão de exemplo (só se ainda não houver nenhuma cadastrada).
if (!$db->query('SELECT 1 FROM ea_goal_templates LIMIT 1')->num_rows) {
    $templates = [
        [null, 'Reduzir a dor para 3 ou menos', 'Educação em dor, analgesia e exercícios graduais.'],
        [null, 'Dormir a noite toda sem acordar com dor', 'Orientação de posição para dormir e controle da dor.'],
        ['lombar', 'Ficar sentado 45 minutos sem dor', 'Pausas ativas, mobilidade lombar e ajuste do posto de trabalho.'],
        ['lombar', 'Caminhar 30 minutos sem dor', 'Fortalecimento de core e progressão de caminhada.'],
        ['cervical', 'Girar o pescoço para dirigir sem dor', 'Mobilização cervical e exercícios de controle motor.'],
        ['ombro', 'Elevar o braço acima da cabeça sem dor', 'Mobilização glenoumeral e fortalecimento do manguito rotador.'],
        ['ombro', 'Pentear o cabelo sem dor', 'Exercícios funcionais de alcance e fortalecimento escapular.'],
        ['joelho', 'Descer escadas sem dor', 'Fortalecimento de quadríceps e glúteos, treino excêntrico.'],
        ['joelho', 'Agachar até 90° sem dor', 'Exercícios em cadeia fechada com progressão de amplitude.'],
        ['tornozelo_pe', 'Andar sem mancar', 'Treino de marcha, equilíbrio e fortalecimento.'],
    ];
    $insert_template = $db->prepare('INSERT INTO ea_goal_templates (area, goal, plan, create_datetime, update_datetime) VALUES (?, ?, ?, NOW(), NOW())');

    foreach ($templates as [$area, $goal, $plan]) {
        $insert_template->bind_param('sss', $area, $goal, $plan);
        $insert_template->execute();
    }
}

$insert_record = $db->prepare(
    'INSERT INTO ea_clinical_records (type, id_users_customer, id_users_author, author_name, id_appointments, record_date,
        pain_scale, content, create_datetime, update_datetime) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)',
);
$insert_log = $db->prepare(
    "INSERT INTO ea_clinical_access_log (id_users, user_name, id_users_customer, id_clinical_records, action, ip_address,
        create_datetime) VALUES (?, ?, ?, ?, 'create', '127.0.0.1', ?)",
);

$created = 0;

foreach ($patients as $email => $data) {
    $patient = $db->query("SELECT id FROM ea_users WHERE email = '" . $db->real_escape_string($email) . "'")->fetch_assoc();

    if (!$patient) {
        echo "Paciente de exemplo não encontrado: {$email}\n";
        continue;
    }

    $patient_id = (int) $patient['id'];

    // Recria do zero só os registros deste paciente de exemplo.
    $db->query("DELETE v FROM ea_clinical_record_versions v JOIN ea_clinical_records r ON r.id = v.id_clinical_records
                WHERE r.id_users_customer = {$patient_id}");
    $db->query("DELETE FROM ea_clinical_access_log WHERE id_users_customer = {$patient_id}");
    $db->query("DELETE FROM ea_clinical_records WHERE id_users_customer = {$patient_id}");

    $entries = array_merge(
        [['type' => 'assessment'] + $data['assessment']],
        array_map(fn($evolution) => ['type' => 'evolution'] + $evolution, $data['evolutions']),
    );

    foreach ($entries as $entry) {
        $content = json_encode($entry['content'], JSON_UNESCAPED_UNICODE);
        $pain = $entry['content']['pain_scale'] ?? null;
        $created_at = $entry['date'];

        $insert_record->bind_param(
            'siississs',
            $entry['type'],
            $patient_id,
            $provider['id'],
            $provider['name'],
            $entry['date'],
            $pain,
            $content,
            $created_at,
            $created_at,
        );
        $insert_record->execute();

        $record_id = $db->insert_id;
        $insert_log->bind_param('isiis', $provider['id'], $provider['name'], $patient_id, $record_id, $created_at);
        $insert_log->execute();

        $created++;
    }
}

echo "Prontuários de exemplo criados: {$created} registros (3 pacientes fictícios).\n";
