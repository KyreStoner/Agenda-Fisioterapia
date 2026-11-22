# Sistema de Agendamento Fisioterapia

> Nome provisório.

Sistema online e responsivo para clínicas de fisioterapia. Funciona no navegador e no celular.

## Funcionalidades planejadas

- **Agendamento online**: o paciente vê os horários disponíveis e marca a consulta pelo celular.
- **Agenda da clínica**: visualizar, confirmar, remarcar e cancelar atendimentos.
- **Cadastro de pacientes**: feito pela equipe da clínica.
- **Evolução clínica**: registro de cada atendimento no histórico do paciente.
- **Protocolos**: modelos de tratamento que podem ser adicionados ao paciente e às evoluções.

## Status

Em avaliação: esta branch usa o [Easy!Appointments](https://github.com/alextselegidis/easyappointments) 1.6.1 como base do agendamento. Os módulos de prontuário (evolução e protocolos) ainda serão desenvolvidos. As decisões e requisitos do projeto estão em [AGENTS.md](AGENTS.md).

## Como rodar localmente

Requisitos: PHP 8.2+, Composer, Node 22+ e MySQL ou MariaDB.

```bash
composer install
npm ci
npm run build
cp config-sample.php config.php   # ajuste BASE_URL, LANGUAGE ('portuguese-br') e dados do banco
php index.php console install      # cria as tabelas e o usuário administrador
php -S 127.0.0.1:8000
```

Acesse `http://127.0.0.1:8000` (agendamento do paciente) e `http://127.0.0.1:8000/index.php/login` (painel da clínica). Troque a senha do administrador no primeiro acesso.

Também é possível usar o `docker-compose.yml` incluído.

## Licença

Baseado no Easy!Appointments, de Alex Tselegidis, distribuído sob a licença [GPL-3.0](LICENSE). Este projeto segue a mesma licença.
