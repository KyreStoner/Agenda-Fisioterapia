<?php
/*
 * Roteador do servidor embutido do PHP, usado só no ambiente de demonstração (Codespaces).
 *
 * O Codespaces entrega o sistema pelo endereço público https://<nome>-8000.app.github.dev,
 * mas repassa a requisição ao servidor com "Host: localhost:8000" e informa o endereço
 * público no cabeçalho X-Forwarded-Host. Sem este ajuste, o sistema monta os links de
 * estilos, scripts e imagens apontando para localhost, e a página abre sem visual.
 *
 * Não use em produção: lá o servidor web (Apache ou Nginx) já recebe o domínio correto.
 */

if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
}

// Segue o comportamento normal do servidor embutido (arquivos estáticos e index.php).
return false;
