<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| App Configuration
|--------------------------------------------------------------------------
|
| Declare some of the global config values of Easy!Appointments.
|
*/

// Nome do sistema, exibido no painel, nos títulos das páginas e nos e-mails.
// Este é só o valor inicial: depois de instalado, o nome é trocado em Configurações → Geral → Nome do sistema.
$config['app_name'] = 'Sistema de Agendamento Fisioterapia';

// Versão do sistema, exibida no rodapé do painel. Aumente a cada entrega (a do Easy!Appointments fica em 'version').
$config['app_version'] = '0.5.0';

// Idiomas. Por enquanto o sistema é todo em português do Brasil (Config::LANGUAGE = 'portuguese-br') e o seletor de
// idioma fica escondido. GANCHO PARA O FUTURO: com true, o seletor volta ao rodapé do painel e da página de
// agendamento e cada usuário pode escolher o idioma, usando application/language/<idioma>/translations_lang.php.
// Antes de ligar, traduza nos idiomas oferecidos as chaves criadas neste projeto (app_name, shift_*, cr_*, ...),
// que hoje existem só em portuguese-br e english.
$config['language_selection'] = false;

$config['version'] = '1.6.1'; // This must be changed manually.

$config['url'] = Config::BASE_URL;

$config['debug'] = Config::DEBUG_MODE;

$config['cache_busting_token'] = 'TSJ83-475';
