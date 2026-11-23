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
pkill -f "php -S 0.0.0.0:8000" 2>/dev/null || true
for _ in $(seq 1 20); do
    (exec 3<>/dev/tcp/127.0.0.1/8000) 2>/dev/null || break
    sleep 0.25
done

# O router.php faz o sistema usar o endereço público do Codespaces (veja o arquivo).
setsid nohup php -S 0.0.0.0:8000 .devcontainer/router.php > /tmp/servidor-php.log 2>&1 < /dev/null &

# Confere se o sistema responde (até 15 segundos).
status=000
for _ in $(seq 1 30); do
    status=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8000/ 2>/dev/null || true)
    [ "${status}" = "200" ] && break
    sleep 0.5
done

if [ "${status}" = "200" ]; then
    echo "OK: sistema funcionando na porta 8000. Abra pela aba PORTAS (ícone de globo) e aperte F5 na página."
else
    echo "ERRO: o sistema não respondeu (código ${status}). Últimas linhas do servidor:"
    tail -n 15 /tmp/servidor-php.log 2>/dev/null || true
    echo "Mande um print desta tela para o assistente."
fi
