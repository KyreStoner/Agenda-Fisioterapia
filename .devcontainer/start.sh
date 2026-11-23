#!/usr/bin/env bash
# Liga o banco e o servidor web sempre que o ambiente é iniciado.
# Se o servidor já estiver rodando, ele é reiniciado para usar a versão atual dos scripts.
set -euo pipefail
cd "$(dirname "$0")/.."

sudo service mariadb start >/dev/null

pkill -f "php -S 0.0.0.0:8000" 2>/dev/null || true
for _ in $(seq 1 20); do
    (exec 3<>/dev/tcp/127.0.0.1/8000) 2>/dev/null || break
    sleep 0.25
done

# O router.php faz o sistema usar o endereço público do Codespaces (veja o arquivo).
setsid nohup php -S 0.0.0.0:8000 .devcontainer/router.php > /tmp/servidor-php.log 2>&1 < /dev/null &
echo "Sistema disponível na porta 8000 (aba PORTAS)."
