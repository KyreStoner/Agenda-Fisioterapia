#!/usr/bin/env bash
# Liga o banco e o servidor web sempre que o ambiente é iniciado.
# Se o servidor já estiver rodando, ele é reiniciado para usar a versão atual dos scripts.
# No fim, confere se o sistema está respondendo e diz o que fazer se não estiver.
set -uo pipefail
cd "$(dirname "$0")/.."

echo "1/4 Ligando o banco de dados..."
sudo service mariadb start >/dev/null || echo "AVISO: o banco de dados não ligou."

# Aplica as alterações novas do banco (migrações), só se o sistema já estiver instalado.
DB_NAME="${DB_NAME:-agenda_fisio}"
DB_USER="${DB_USER:-agenda}"
DB_PASS="${DB_PASS:-agenda-dev-local}"   # Apenas para o ambiente local de demonstração.
echo "2/4 Atualizando o banco (migrações)..."
if [ -f config.php ] && mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "SELECT 1 FROM ea_settings LIMIT 1" >/dev/null 2>&1; then
    php index.php console migrate || echo "AVISO: falha ao atualizar o banco. O servidor vai subir mesmo assim; mande esta mensagem para o assistente."
fi

# Recompila JS e CSS, para mudanças em assets/ valerem também num Codespace já criado.
echo "3/4 Recompilando JS e CSS (cerca de 20 segundos)..."
if [ -d node_modules ]; then
    npx gulp scripts styles > /tmp/gulp-assets.log 2>&1 || echo "AVISO: não foi possível recompilar JS/CSS (veja /tmp/gulp-assets.log)."
fi

echo "4/4 Reiniciando o servidor na porta 8000..."
pkill -f "php -S .*:8000" 2>/dev/null || true
for _ in $(seq 1 20); do
    (exec 3<>/dev/tcp/127.0.0.1/8000) 2>/dev/null || break
    sleep 0.25
done

responds() { curl -s -o /dev/null -m 3 -w '%{http_code}' "http://$1:8000/" 2>/dev/null || true; }

# O router.php faz o sistema usar o endereço público do Codespaces (veja o arquivo).
start_server() { setsid nohup php -S "$1:8000" .devcontainer/router.php > /tmp/servidor-php.log 2>&1 < /dev/null & }

# Escuta em IPv6 e IPv4 ao mesmo tempo ([::]), porque o encaminhamento de porta pode chegar por qualquer um dos
# dois. Se o ambiente não tiver IPv6, o servidor nem sobe assim; aí usa só IPv4 (0.0.0.0).
start_server '[::]'
sleep 1
if ! pgrep -f "php -S \[::\]:8000" >/dev/null; then
    start_server 0.0.0.0
fi

# Confere se o sistema responde (até 15 segundos).
status=000
for _ in $(seq 1 30); do
    status=$(responds 127.0.0.1)
    [ "${status}" = "200" ] && break
    sleep 0.5
done

echo "Teste interno: IPv4 ${status} | IPv6 $(responds '[::1]') (200 = funcionando; 000 no IPv6 é normal se o ambiente não tiver IPv6)"

if [ "${status}" = "200" ]; then
    echo "OK: sistema funcionando na porta 8000."
    if [ -n "${CODESPACE_NAME:-}" ]; then
        echo "Endereço público: https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}/login"
    fi
    echo "Se o endereço público der 404, segure Ctrl e clique neste link (o Codespaces refaz o encaminhamento da porta):"
    echo "    http://localhost:8000/login"
else
    echo "ERRO: o sistema não respondeu (código ${status}). Últimas linhas do servidor:"
    tail -n 15 /tmp/servidor-php.log 2>/dev/null || true
    echo "Mande um print desta tela para o assistente."
fi
