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
            'label' => 'Coluna torácica',
            'tests' => ['adams' => 'Teste de Adams'],
            'questionnaires' => [],
        ],
        'lombar' => [
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
            'label' => 'Punho e mão',
            'tests' => [
                'phalen' => 'Teste de Phalen',
                'tinel' => 'Sinal de Tinel',
                'finkelstein' => 'Teste de Finkelstein',
            ],
            'questionnaires' => ['dash' => 'DASH', 'boston' => 'Questionário de Boston (túnel do carpo)'],
        ],
        'quadril' => [
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

    // Resultado de cada teste especial.
    'test_results' => [
        'negative' => 'Negativo',
        'positive' => 'Positivo',
        'not_done' => 'Não realizado',
    ],
];
