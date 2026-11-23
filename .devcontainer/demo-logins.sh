#!/usr/bin/env bash
# Logins FICTÍCIOS do ambiente de demonstração (Codespaces). Nunca use estas senhas em produção.
#
#   Fisioterapeuta (vê o prontuário):            fisio    / fisio1234
#   Recepcionista (cadastro e agenda, sem prontuário): recepcao / recepcao1234
#
# Pode rodar quantas vezes quiser: redefine as senhas e cria a recepcionista se ela não existir.
set -euo pipefail
cd "$(dirname "$0")/.."

DB_NAME="${DB_NAME:-agenda_fisio}"
DB_USER="${DB_USER:-agenda}"
DB_PASS="${DB_PASS:-agenda-dev-local}"   # Apenas para o ambiente local de demonstração.

hash() { php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT, ["cost" => 12]);' "$1"; }
fisio_hash=$(hash 'fisio1234')
recepcao_hash=$(hash 'recepcao1234')

mysql --default-character-set=utf8mb4 -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" <<SQL
SET @fisio = (SELECT u.id FROM ea_users u JOIN ea_roles r ON r.id = u.id_roles WHERE r.slug = 'provider' ORDER BY u.id LIMIT 1);
UPDATE ea_user_settings SET username = 'fisio', password = '${fisio_hash}', salt = '' WHERE id_users = @fisio;

SET @recepcao = (SELECT u.id FROM ea_users u JOIN ea_roles r ON r.id = u.id_roles
                 WHERE r.slug = 'secretary' AND u.email = 'recepcao@clinica-exemplo.com.br' LIMIT 1);
INSERT INTO ea_users (first_name, last_name, email, phone_number, id_roles, timezone, language)
SELECT 'Paula', 'Exemplo (Recepção)', 'recepcao@clinica-exemplo.com.br', '(11) 90000-0100', r.id,
       'America/Sao_Paulo', 'portuguese-br'
FROM ea_roles r WHERE r.slug = 'secretary' AND @recepcao IS NULL;
SET @recepcao = COALESCE(@recepcao, LAST_INSERT_ID());

INSERT INTO ea_user_settings (id_users, username, password, salt, notifications, calendar_view)
VALUES (@recepcao, 'recepcao', '${recepcao_hash}', '', 0, 'default')
ON DUPLICATE KEY UPDATE username = 'recepcao', password = '${recepcao_hash}', salt = '';

INSERT IGNORE INTO ea_secretaries_providers (id_users_secretary, id_users_provider) VALUES (@recepcao, @fisio);
SQL

echo "Logins de demonstração: fisioterapeuta fisio / fisio1234 | recepcionista recepcao / recepcao1234"
