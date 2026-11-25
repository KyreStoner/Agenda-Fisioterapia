<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Paletas de cores
|--------------------------------------------------------------------------
|
| Paletas oferecidas em Configurações → Geral → Paleta de cores (vale para o sistema todo: painel, página de
| agendamento do paciente, login e e-mails) e em Conta → Cores do sistema (cada usuário pode escolher outra só para
| ele ver o painel).
|
| Para acrescentar uma paleta, copie um bloco e troque o id (a chave), o nome e as cores. O id fica salvo no banco:
| não renomeie nem apague o id de uma paleta que já esteja em uso (quem usava volta para o verde original).
|
| Papel de cada cor:
|   primary    Botões, links, caixas marcadas, horário escolhido. Precisa ter bom contraste com texto branco.
|   dark       Barra do menu do painel e cabeçalho da página de agendamento (texto branco por cima).
|   accent     Detalhes: item do menu aberto, etapa atual do agendamento, item selecionado nas listas.
|   light      Fundos suaves: destaques, dia de hoje na agenda, avisos.
|   background (opcional) Cor de fundo das páginas. Se faltar, é calculada a partir da "light".
|   swatches   As cores como vieram da paleta original, na ordem, para mostrar na tela de escolha.
|
| O id "default" é reservado ao verde original do tema e o id "custom" à cor personalizada (uma cor só, escolhida
| em Configurações → Geral). Nenhum dos dois pode ser usado aqui.
|
*/

$config['color_palettes'] = [
    'marinho' => [
        'name' => 'Azul-marinho',
        'swatches' => ['#131B2B', '#2F4A63', '#5B91A3', '#CFEAE0'],
        'primary' => '#2F4A63',
        'dark' => '#131B2B',
        'accent' => '#5B91A3',
        'light' => '#CFEAE0',
    ],

    'oliva' => [
        'name' => 'Verde-oliva',
        'swatches' => ['#EBEBD8', '#637545', '#455230', '#9BA685'],
        'primary' => '#637545',
        'dark' => '#455230',
        'accent' => '#9BA685',
        'light' => '#EBEBD8',
    ],

    'sereno' => [
        'name' => 'Azul sereno',
        'swatches' => ['#3368A0', '#66A3BF', '#C8DFDB', '#F2EFE7'],
        'primary' => '#3368A0',
        'dark' => '#3368A0',
        'accent' => '#66A3BF',
        'light' => '#C8DFDB',
        'background' => '#F2EFE7',
    ],

    'vinho' => [
        'name' => 'Vinho e coral',
        'swatches' => ['#5E244E', '#AA1C41', '#E68457', '#FFE8B4'],
        'primary' => '#AA1C41',
        'dark' => '#5E244E',
        'accent' => '#E68457',
        'light' => '#FFE8B4',
    ],
];

// Verde original do tema (id "default"): não troca nenhuma cor, só aparece na tela de escolha com estas amostras.
$config['color_palette_original'] = [
    'name' => 'Verde (original)',
    'swatches' => ['#2C6B5A', '#429A82', '#8CC7B5', '#E4F2EE'],
    'primary' => '#429A82',
];
