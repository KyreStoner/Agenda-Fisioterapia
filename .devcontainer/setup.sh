#!/usr/bin/env bash
# Prepara o sistema na primeira vez que o ambiente é criado:
# dependências, build dos assets, banco de dados e dados fictícios de demonstração.
set -euo pipefail
cd "$(dirname "$0")/.."

DB_NAME="${DB_NAME:-agenda_fisio}"
DB_USER="${DB_USER:-agenda}"
DB_PASS="${DB_PASS:-agenda-dev-local}"   # Apenas para o ambiente local de demonstração.

sudo service mariadb start

composer install --no-interaction --no-progress
npm ci --no-audit --no-fund
npx gulp compile

sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
    FLUSH PRIVILEGES;"

if [ ! -f config.php ]; then
    sed -e "s#const BASE_URL = 'http://localhost';#const BASE_URL = 'http://localhost:8000';#" \
        -e "s#const LANGUAGE = 'english';#const LANGUAGE = 'portuguese-br';#" \
        -e "s#const DB_HOST = 'mysql';#const DB_HOST = 'localhost';#" \
        -e "s#const DB_NAME = 'easyappointments';#const DB_NAME = '${DB_NAME}';#" \
        -e "s#const DB_USERNAME = 'user';#const DB_USERNAME = '${DB_USER}';#" \
        -e "s#const DB_PASSWORD = 'password';#const DB_PASSWORD = '${DB_PASS}';#" \
        config-sample.php > config.php
fi

# Instala somente se o banco ainda estiver vazio, para nunca apagar dados existentes.
if ! mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "SELECT 1 FROM ea_settings LIMIT 1" >/dev/null 2>&1; then
    php index.php console install
    DB_NAME="${DB_NAME}" DB_USER="${DB_USER}" DB_PASS="${DB_PASS}" bash .devcontainer/demo-data.sh
fi
