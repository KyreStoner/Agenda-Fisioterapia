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

$config['version'] = '1.6.1'; // This must be changed manually.

$config['url'] = Config::BASE_URL;

$config['debug'] = Config::DEBUG_MODE;

$config['cache_busting_token'] = 'TSJ83-475';
