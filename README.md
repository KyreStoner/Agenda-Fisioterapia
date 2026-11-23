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

## Ver o sistema funcionando (GitHub Codespaces)

Não precisa instalar nada no computador.

**Atalho:** [abrir no Codespaces](https://codespaces.new/KyreStoner/Agenda-Fisioterapia/tree/base-easyappointments?quickstart=1) (cria o ambiente na primeira vez e retoma o mesmo nas próximas).

1. Ou, no GitHub, abra a branch do projeto e clique em **Code → Codespaces → Create codespace**.
2. Aguarde a preparação. Na primeira vez leva alguns minutos, porque instala tudo e carrega dados **fictícios** de demonstração.
3. O sistema abre numa nova aba. Se não abrir, vá na aba **PORTAS** (Ports) e clique no endereço da porta **8000**.

- Agendamento do paciente: o endereço principal.
- Painel da clínica: acrescente `/index.php/login` ao endereço. Usuário `administrator`, senha `administrator`.

Se a página abrir só com texto, sem cores e sem botões, abra a aba **Terminal**, rode `bash .devcontainer/start.sh` e recarregue a página.

O link é privado (só abre com a sua conta do GitHub). O Codespace desliga sozinho após um tempo sem uso. Para voltar, abra **Code → Codespaces** e clique nele novamente.

Os scripts ficam em `.devcontainer/`. Para recarregar só os dados de demonstração em um banco limpo, rode `php index.php console install` e depois `bash .devcontainer/demo-data.sh`.

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
