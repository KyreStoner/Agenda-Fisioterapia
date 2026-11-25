#!/usr/bin/env bash
# Substitui os dados de exemplo da instalação por dados FICTÍCIOS de uma clínica de fisioterapia.
# As consultas são criadas nos próximos dias úteis a partir da data atual.
set -euo pipefail

DB_NAME="${DB_NAME:-agenda_fisio}"
DB_USER="${DB_USER:-agenda}"
DB_PASS="${DB_PASS:-agenda-dev-local}"

# Próximos 5 dias úteis (segunda a sexta), começando hoje.
days=()
offset=0
while [ "${#days[@]}" -lt 5 ]; do
    day=$(date -d "+${offset} day" +%F)
    [ "$(date -d "$day" +%u)" -le 5 ] && days+=("$day")
    offset=$((offset + 1))
done

working_plan='{"monday":{"start":"08:00","end":"18:00","breaks":[{"start":"12:00","end":"13:00"}]},"tuesday":{"start":"08:00","end":"18:00","breaks":[{"start":"12:00","end":"13:00"}]},"wednesday":{"start":"08:00","end":"18:00","breaks":[{"start":"12:00","end":"13:00"}]},"thursday":{"start":"08:00","end":"18:00","breaks":[{"start":"12:00","end":"13:00"}]},"friday":{"start":"08:00","end":"18:00","breaks":[{"start":"12:00","end":"13:00"}]},"saturday":{"start":"08:00","end":"12:00","breaks":[]},"sunday":null}'

appointment() { # dia hora id_servico email_paciente
    local minutes=50 color='#7cbae8'
    [ "$3" = '@avaliacao' ] && minutes=60 color='#a8d5a2'
    echo "INSERT INTO ea_appointments (book_datetime, start_datetime, end_datetime, notes, hash, status, is_unavailability, id_users_provider, id_users_customer, id_services, color)
          VALUES (NOW(), '$1 $2:00', DATE_ADD('$1 $2:00', INTERVAL $minutes MINUTE), '', MD5(RAND()), 'Booked', 0, @fisio,
                  (SELECT id FROM ea_users WHERE email = '$4'), $3, '$color');"
}

{
cat <<SQL
SET @admin  = (SELECT u.id FROM ea_users u JOIN ea_roles r ON r.id = u.id_roles WHERE r.slug = 'admin' ORDER BY u.id LIMIT 1);
SET @fisio  = (SELECT u.id FROM ea_users u JOIN ea_roles r ON r.id = u.id_roles WHERE r.slug = 'provider' ORDER BY u.id LIMIT 1);
SET @sessao = (SELECT MIN(id) FROM ea_services);

UPDATE ea_settings SET value = 'Clínica Exemplo de Fisioterapia' WHERE name = 'company_name';
UPDATE ea_settings SET value = 'contato@clinica-exemplo.com.br' WHERE name = 'company_email';
UPDATE ea_settings SET value = 'military' WHERE name = 'time_format';
UPDATE ea_settings SET value = 'monday' WHERE name = 'first_weekday';
UPDATE ea_settings SET value = 'portuguese-br' WHERE name = 'default_language';
UPDATE ea_settings SET value = 'America/Sao_Paulo' WHERE name = 'default_timezone';
UPDATE ea_settings SET value = '${working_plan}' WHERE name = 'company_working_plan';

UPDATE ea_users SET timezone = 'America/Sao_Paulo', language = 'portuguese-br';
UPDATE ea_users SET first_name = 'Administrador', last_name = 'da Clínica' WHERE id = @admin;
UPDATE ea_users SET first_name = 'Ana', last_name = 'Exemplo (Fisioterapeuta)', email = 'ana@clinica-exemplo.com.br' WHERE id = @fisio;
UPDATE ea_user_settings SET working_plan = '${working_plan}' WHERE id_users = @fisio;
DELETE FROM ea_provider_shifts WHERE id_users_provider = @fisio;
INSERT INTO ea_provider_shifts (id_users_provider, weekday, frequency, start_time, end_time, break_start, break_end, duration, notes, create_datetime, update_datetime)
VALUES (@fisio, 'weekdays', 'weekly', '08:00', '18:00', '12:00', '13:00', 60, 'Almoço das 12h às 13h', NOW(), NOW()),
       (@fisio, 'saturday', 'weekly', '08:00', '12:00', NULL, NULL, 60, NULL, NOW(), NOW());

UPDATE ea_services SET name = 'Sessão de Fisioterapia', duration = 50, price = 150.00, currency = 'R\$',
       slot_interval = 60, description = 'Atendimento individual de fisioterapia.' WHERE id = @sessao;
INSERT INTO ea_services (name, duration, price, currency, slot_interval, description, attendants_number, color)
       VALUES ('Avaliação Fisioterapêutica', 60, 200.00, 'R\$', 60, 'Primeira consulta com avaliação completa.', 1,
               '#a8d5a2');
SET @avaliacao = LAST_INSERT_ID();
INSERT INTO ea_services_providers (id_users, id_services) VALUES (@fisio, @avaliacao);

DELETE FROM ea_users WHERE id_roles = (SELECT id FROM ea_roles WHERE slug = 'customer');
INSERT INTO ea_users (first_name, last_name, email, phone_number, id_roles, timezone, language)
SELECT p.first_name, p.last_name, p.email, p.phone, r.id, 'America/Sao_Paulo', 'portuguese-br'
FROM ea_roles r
JOIN (
    SELECT 'Carlos' first_name, 'Fictício' last_name, 'paciente1@exemplo.com.br' email, '(11) 90000-0001' phone UNION ALL
    SELECT 'Mariana', 'Exemplo', 'paciente2@exemplo.com.br', '(11) 90000-0002' UNION ALL
    SELECT 'João', 'Teste', 'paciente3@exemplo.com.br', '(11) 90000-0003' UNION ALL
    SELECT 'Beatriz', 'Modelo', 'paciente4@exemplo.com.br', '(11) 90000-0004' UNION ALL
    SELECT 'Rafael', 'Demonstração', 'paciente5@exemplo.com.br', '(11) 90000-0005' UNION ALL
    SELECT 'Lúcia', 'Amostra', 'paciente6@exemplo.com.br', '(11) 90000-0006'
) p
WHERE r.slug = 'customer';
SQL
appointment "${days[0]}" 08:00 @sessao    paciente1@exemplo.com.br
appointment "${days[0]}" 09:00 @avaliacao paciente2@exemplo.com.br
appointment "${days[0]}" 10:00 @sessao    paciente3@exemplo.com.br
appointment "${days[0]}" 14:00 @sessao    paciente4@exemplo.com.br
appointment "${days[1]}" 08:00 @sessao    paciente5@exemplo.com.br
appointment "${days[1]}" 11:00 @avaliacao paciente6@exemplo.com.br
appointment "${days[1]}" 15:00 @sessao    paciente1@exemplo.com.br
appointment "${days[2]}" 09:00 @sessao    paciente2@exemplo.com.br
appointment "${days[2]}" 13:00 @sessao    paciente3@exemplo.com.br
appointment "${days[3]}" 10:00 @avaliacao paciente4@exemplo.com.br
appointment "${days[4]}" 16:00 @sessao    paciente5@exemplo.com.br
} | mysql --default-character-set=utf8mb4 -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"

DB_NAME="${DB_NAME}" DB_USER="${DB_USER}" DB_PASS="${DB_PASS}" bash "$(dirname "$0")/demo-logins.sh"
DB_NAME="${DB_NAME}" DB_USER="${DB_USER}" DB_PASS="${DB_PASS}" php "$(dirname "$0")/demo-records.php"

echo "Dados fictícios de demonstração carregados."
