#!/usr/bin/env bash
# Liga o banco e o servidor web sempre que o ambiente é iniciado.
# Se o servidor já estiver rodando, ele é reiniciado para usar a versão atual dos scripts.
set -euo pipefail
cd "$(dirname "$0")/.."

sudo service mariadb start >/dev/null

# Aplica as alterações novas do banco (migrações), só se o sistema já estiver instalado.
DB_NAME="${DB_NAME:-agenda_fisio}"
DB_USER="${DB_USER:-agenda}"
DB_PASS="${DB_PASS:-agenda-dev-local}"   # Apenas para o ambiente local de demonstração.
if [ -f config.php ] && mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "SELECT 1 FROM ea_settings LIMIT 1" >/dev/null 2>&1; then
    php index.php console migrate
fi

pkill -f "php -S 0.0.0.0:8000" 2>/dev/null || true
for _ in $(seq 1 20); do
    (exec 3<>/dev/tcp/127.0.0.1/8000) 2>/dev/null || break
    sleep 0.25
done

# O router.php faz o sistema usar o endereço público do Codespaces (veja o arquivo).
setsid nohup php -S 0.0.0.0:8000 .devcontainer/router.php > /tmp/servidor-php.log 2>&1 < /dev/null &
echo "Sistema disponível na porta 8000 (aba PORTAS)."
