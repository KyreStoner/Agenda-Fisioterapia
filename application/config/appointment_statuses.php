<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Situações dos atendimentos (cores da agenda)
|--------------------------------------------------------------------------
|
| A agenda pinta cada atendimento pela situação dele. A lista que vale é a de Configurações → Agendamento →
| Situações dos atendimentos (configurações "appointment_status_options" e "appointment_status_colors"), onde a
| clínica pode renomear, trocar a cor, acrescentar ou tirar situações. A primeira da lista é a de toda consulta nova.
|
| Esta é a lista padrão (migração 077) e a fonte das cores para uma situação sem cor salva. A ordem segue o caminho do
| atendimento: marcado → confirmado → chegou → em atendimento → executado.
|
*/

$config['appointment_statuses'] = [
    ['name' => 'Marcado', 'color' => '#7cbae8', 'hint' => 'Consulta marcada. É a situação de toda consulta nova.'],
    ['name' => 'Encaixado', 'color' => '#f3bc7d', 'hint' => 'Encaixe num horário que já estava ocupado.'],
    ['name' => 'Confirmado', 'color' => '#82e4ec', 'hint' => 'O paciente confirmou que vem.'],
    ['name' => 'Aguardando', 'color' => '#ebe07c', 'hint' => 'O paciente chegou e a recepção registrou a chegada.'],
    ['name' => 'Em atendimento', 'color' => '#c9b3f5', 'hint' => 'O profissional começou o atendimento.'],
    ['name' => 'Executado', 'color' => '#abe9a4', 'hint' => 'O profissional terminou o atendimento.'],
    ['name' => 'Faltou', 'color' => '#f3aea6', 'hint' => 'O paciente não veio.'],
    ['name' => 'Cancelado', 'color' => '#d5d5d5', 'hint' => 'Consulta cancelada.'],
];

// Cor de uma situação nova, acrescentada na tela de configurações sem escolher cor.
$config['appointment_status_default_color'] = '#7cbae8';

// Mudanças automáticas de situação, pelo nome (se renomear a situação em Configurações, ajuste aqui também; se o
// nome não estiver na lista, a mudança automática simplesmente não acontece):
//  - overbooked: ao confirmar um encaixe na agenda (o aviso de horário ocupado → "Continuar"), a consulta que estava na
//    primeira situação da lista (Marcado) passa para esta;
//  - completed: ao salvar um registro do prontuário aberto a partir da consulta, ela passa para esta, a não ser que
//    esteja numa das situações de completed_keep.
$config['appointment_status_automation'] = [
    'overbooked' => 'Encaixado',
    'completed' => 'Executado',
    'completed_keep' => ['Executado', 'Faltou', 'Cancelado'],
];
