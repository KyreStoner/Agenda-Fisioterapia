#!/usr/bin/env bash
# Liga o banco e o servidor web sempre que o ambiente é iniciado.
set -euo pipefail
cd "$(dirname "$0")/.."

sudo service mariadb start >/dev/null

if ! (exec 3<>/dev/tcp/127.0.0.1/8000) 2>/dev/null; then
    setsid nohup php -S 0.0.0.0:8000 > /tmp/servidor-php.log 2>&1 < /dev/null &
fi
echo "Sistema disponível na porta 8000 (aba PORTAS)."
