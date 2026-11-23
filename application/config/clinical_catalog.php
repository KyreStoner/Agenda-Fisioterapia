<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Catálogo clínico: regiões do corpo, testes especiais e questionários
|--------------------------------------------------------------------------
|
| Usado pelo mapa corporal da dor e pelos testes que aparecem no Exame físico.
|
| - regions: regiões clicáveis do bonequinho. "area" liga a região ao grupo de testes; "side" é o lado do paciente
|   (d = direito, e = esquerdo, null = linha média).
| - areas: para cada área, os testes especiais e os questionários sugeridos.
|
| LISTA INICIAL: a usuária vai enviar as fichas da clínica; quando chegarem, troque os testes e questionários aqui.
| Ids (chaves) ficam salvos no prontuário: não renomeie um id que já foi usado, só acrescente ou mude o nome exibido.
|
*/

$config['clinical_catalog'] = [
    'regions' => [
        'cabeca' => ['label' => 'Cabeça', 'area' => null, 'side' => null],
        'cervical' => ['label' => 'Pescoço (coluna cervical)', 'area' => 'cervical', 'side' => null],
        'torax' => ['label' => 'Tórax', 'area' => null, 'side' => null],
        'abdome' => ['label' => 'Abdome', 'area' => null, 'side' => null],
        'coluna_toracica' => ['label' => 'Coluna torácica', 'area' => 'toracica', 'side' => null],
        'coluna_lombar' => ['label' => 'Coluna lombar', 'area' => 'lombar', 'side' => null],
        'ombro_d' => ['label' => 'Ombro direito', 'area' => 'ombro', 'side' => 'd'],
        'ombro_e' => ['label' => 'Ombro esquerdo', 'area' => 'ombro', 'side' => 'e'],
        'braco_d' => ['label' => 'Braço direito', 'area' => 'ombro', 'side' => 'd'],
        'braco_e' => ['label' => 'Braço esquerdo', 'area' => 'ombro', 'side' => 'e'],
        'cotovelo_d' => ['label' => 'Cotovelo direito', 'area' => 'cotovelo', 'side' => 'd'],
        'cotovelo_e' => ['label' => 'Cotovelo esquerdo', 'area' => 'cotovelo', 'side' => 'e'],
        'antebraco_d' => ['label' => 'Antebraço direito', 'area' => 'cotovelo', 'side' => 'd'],
        'antebraco_e' => ['label' => 'Antebraço esquerdo', 'area' => 'cotovelo', 'side' => 'e'],
        'punho_mao_d' => ['label' => 'Punho e mão direitos', 'area' => 'punho_mao', 'side' => 'd'],
        'punho_mao_e' => ['label' => 'Punho e mão esquerdos', 'area' => 'punho_mao', 'side' => 'e'],
        'quadril_d' => ['label' => 'Quadril direito', 'area' => 'quadril', 'side' => 'd'],
        'quadril_e' => ['label' => 'Quadril esquerdo', 'area' => 'quadril', 'side' => 'e'],
        'coxa_d' => ['label' => 'Coxa direita', 'area' => 'quadril', 'side' => 'd'],
        'coxa_e' => ['label' => 'Coxa esquerda', 'area' => 'quadril', 'side' => 'e'],
        'joelho_d' => ['label' => 'Joelho direito', 'area' => 'joelho', 'side' => 'd'],
        'joelho_e' => ['label' => 'Joelho esquerdo', 'area' => 'joelho', 'side' => 'e'],
        'perna_d' => ['label' => 'Perna direita', 'area' => 'joelho', 'side' => 'd'],
        'perna_e' => ['label' => 'Perna esquerda', 'area' => 'joelho', 'side' => 'e'],
        'tornozelo_pe_d' => ['label' => 'Tornozelo e pé direitos', 'area' => 'tornozelo_pe', 'side' => 'd'],
        'tornozelo_pe_e' => ['label' => 'Tornozelo e pé esquerdos', 'area' => 'tornozelo_pe', 'side' => 'e'],
    ],

    'areas' => [
        'cervical' => [
            'quadrant' => 'upper',
            'label' => 'Coluna cervical',
            'tests' => [
                'spurling' => 'Teste de Spurling',
                'distracao_cervical' => 'Teste de distração cervical',
                'ultt' => 'Teste de tensão neural do membro superior (ULTT)',
                'flexao_rotacao' => 'Teste de flexão-rotação cervical',
            ],
            'questionnaires' => ['ndi' => 'Neck Disability Index (NDI)'],
        ],
        'toracica' => [
            'quadrant' => null,
            'label' => 'Coluna torácica',
            'tests' => ['adams' => 'Teste de Adams'],
            'questionnaires' => [],
        ],
        'lombar' => [
            'quadrant' => 'lower',
            'label' => 'Coluna lombar',
            'tests' => [
                'lasegue' => 'Teste de Lasègue (elevação da perna estendida)',
                'slump' => 'Slump test',
                'schober' => 'Teste de Schober',
                'instabilidade_prono' => 'Teste de instabilidade em prono',
            ],
            'questionnaires' => ['oswestry' => 'Oswestry (ODI)', 'roland_morris' => 'Roland-Morris'],
        ],
        'ombro' => [
            'quadrant' => 'upper',
            'label' => 'Ombro',
            'tests' => [
                'neer' => 'Teste de Neer',
                'hawkins' => 'Teste de Hawkins-Kennedy',
                'jobe' => 'Teste de Jobe (lata vazia)',
                'patte' => 'Teste de Patte',
                'speed' => 'Teste de Speed',
                'apreensao' => 'Teste de apreensão',
            ],
            'questionnaires' => ['spadi' => 'SPADI', 'dash' => 'DASH'],
        ],
        'cotovelo' => [
            'quadrant' => 'upper',
            'label' => 'Cotovelo',
            'tests' => [
                'cozen' => 'Teste de Cozen',
                'mill' => 'Teste de Mill',
                'golfista' => 'Teste do cotovelo de golfista',
                'estresse_valgo_cotovelo' => 'Estresse em valgo',
                'estresse_varo_cotovelo' => 'Estresse em varo',
            ],
            'questionnaires' => ['dash' => 'DASH', 'prtee' => 'PRTEE'],
        ],
        'punho_mao' => [
            'quadrant' => 'upper',
            'label' => 'Punho e mão',
            'tests' => [
                'phalen' => 'Teste de Phalen',
                'tinel' => 'Sinal de Tinel',
                'finkelstein' => 'Teste de Finkelstein',
            ],
            'questionnaires' => ['dash' => 'DASH', 'boston' => 'Questionário de Boston (túnel do carpo)'],
        ],
        'quadril' => [
            'quadrant' => 'lower',
            'label' => 'Quadril',
            'tests' => [
                'faber' => 'Teste de Patrick (FABER)',
                'fadir' => 'FADIR',
                'thomas' => 'Teste de Thomas',
                'trendelenburg' => 'Sinal de Trendelenburg',
                'ober' => 'Teste de Ober',
            ],
            'questionnaires' => ['womac' => 'WOMAC', 'hoos' => 'HOOS'],
        ],
        'joelho' => [
            'quadrant' => 'lower',
            'label' => 'Joelho',
            'tests' => [
                'lachman' => 'Teste de Lachman',
                'gaveta_anterior' => 'Gaveta anterior',
                'gaveta_posterior' => 'Gaveta posterior',
                'mcmurray' => 'Teste de McMurray',
                'apley' => 'Teste de Apley',
                'estresse_valgo_joelho' => 'Estresse em valgo',
                'estresse_varo_joelho' => 'Estresse em varo',
            ],
            'questionnaires' => ['lysholm' => 'Lysholm', 'koos' => 'KOOS', 'womac' => 'WOMAC'],
        ],
        'tornozelo_pe' => [
            'quadrant' => 'lower',
            'label' => 'Tornozelo e pé',
            'tests' => [
                'gaveta_tornozelo' => 'Gaveta anterior do tornozelo',
                'inclinacao_talar' => 'Inclinação talar',
                'thompson' => 'Teste de Thompson',
                'windlass' => 'Teste de Windlass',
            ],
            'questionnaires' => ['faam' => 'FAAM', 'aofas' => 'AOFAS'],
        ],
    ],

    // Exame neurológico (dermátomos e miótomos). "quadrant": aparece quando há região marcada no membro superior/coluna
    // cervical (upper) ou no membro inferior/coluna lombar (lower), conforme o "quadrant" de cada área acima.
    'neuro_levels' => [
        'C4' => ['quadrant' => 'upper', 'dermatome' => 'Região do ombro, acima da clavícula', 'myotome' => 'Elevação dos ombros'],
        'C5' => ['quadrant' => 'upper', 'dermatome' => 'Face lateral do braço', 'myotome' => 'Abdução do ombro'],
        'C6' => ['quadrant' => 'upper', 'dermatome' => 'Face lateral do antebraço e polegar', 'myotome' => 'Flexão do cotovelo e extensão do punho'],
        'C7' => ['quadrant' => 'upper', 'dermatome' => 'Dedo médio', 'myotome' => 'Extensão do cotovelo e flexão do punho'],
        'C8' => ['quadrant' => 'upper', 'dermatome' => 'Dedo mínimo e borda medial da mão', 'myotome' => 'Flexão dos dedos'],
        'T1' => ['quadrant' => 'upper', 'dermatome' => 'Face medial do antebraço', 'myotome' => 'Abdução dos dedos'],
        'L2' => ['quadrant' => 'lower', 'dermatome' => 'Face anterior da coxa', 'myotome' => 'Flexão do quadril'],
        'L3' => ['quadrant' => 'lower', 'dermatome' => 'Face medial do joelho', 'myotome' => 'Extensão do joelho'],
        'L4' => ['quadrant' => 'lower', 'dermatome' => 'Face medial da perna e maléolo medial', 'myotome' => 'Dorsiflexão do tornozelo'],
        'L5' => ['quadrant' => 'lower', 'dermatome' => 'Dorso do pé e hálux', 'myotome' => 'Extensão do hálux'],
        'S1' => ['quadrant' => 'lower', 'dermatome' => 'Borda lateral do pé e calcanhar', 'myotome' => 'Flexão plantar'],
    ],

    'sensation_results' => [
        'normal' => 'Normal',
        'decreased' => 'Diminuída',
        'increased' => 'Aumentada',
        'absent' => 'Ausente',
    ],

    // Resultado de cada teste especial.
    'test_results' => [
        'negative' => 'Negativo',
        'positive' => 'Positivo',
        'not_done' => 'Não realizado',
    ],
];
