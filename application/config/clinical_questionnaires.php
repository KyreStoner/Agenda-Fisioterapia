<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Questionários com perguntas (pontuação automática)
|--------------------------------------------------------------------------
|
| Cada questionário com perguntas aparece no Exame físico com o botão "Responder": a fisioterapeuta marca a resposta
| do paciente em cada pergunta e o sistema soma a pontuação sozinho (e guarda as respostas no prontuário).
|
| COMO CADASTRAR UM QUESTIONÁRIO DA CLÍNICA:
|  - Use como chave o mesmo id da lista de questionários da área em clinical_catalog.php (ex.: 'spadi', 'oswestry').
|    Para um questionário novo, acrescente o id também na lista da(s) área(s) no catálogo, ou use 'areas'.
|  - 'scoring': 'sum' (soma dos pontos, mostra "pontos/máximo") ou 'percent' (soma ÷ máximo × 100).
|  - 'questions': lista de perguntas; cada uma com 'text' e 'options' = [['texto da resposta', pontos], ...].
|  - 'areas' (opcional): ids de áreas onde ele também aparece; ['*'] = todas.
|  - 'interpretation' (opcional): como ler o resultado, mostrado embaixo do questionário.
|
| Atenção a direitos autorais: vários questionários clínicos têm licença de uso. Cadastre as versões que a clínica tem
| direito de usar.
|
| O "exemplo_demo" abaixo é só uma DEMONSTRAÇÃO do funcionamento: apague quando cadastrar os da clínica.
|
*/

$config['clinical_questionnaires'] = [
    'exemplo_demo' => [
        'label' => 'Questionário de exemplo (demonstração – apagar)',
        'areas' => ['*'],
        'scoring' => 'sum',
        'interpretation' => 'Exemplo: quanto maior a pontuação, maior a limitação.',
        'questions' => [
            [
                'text' => 'Consegue abrir a tampa de um pote?',
                'options' => [['Sem dificuldade', 0], ['Com dificuldade', 1], ['Não consegue', 2]],
            ],
            [
                'text' => 'Consegue subir um lance de escada?',
                'options' => [['Sem dificuldade', 0], ['Com dificuldade', 1], ['Não consegue', 2]],
            ],
            [
                'text' => 'A dor atrapalha o sono?',
                'options' => [['Nunca', 0], ['Às vezes', 1], ['Quase todas as noites', 2]],
            ],
            [
                'text' => 'Consegue carregar sacolas de compras?',
                'options' => [['Sem dificuldade', 0], ['Com dificuldade', 1], ['Não consegue', 2]],
            ],
        ],
    ],
];
