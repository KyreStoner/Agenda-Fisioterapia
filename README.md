# Sistema de Agendamento Fisioterapia

> Nome provisório.

Sistema online e responsivo para clínicas de fisioterapia. Funciona no navegador e no celular.

## Funcionalidades planejadas

- **Agendamento online** (pronto): o paciente vê os horários disponíveis e marca a consulta pelo celular.
- **Agenda da clínica** (pronto): visualizar, confirmar, remarcar e cancelar atendimentos.
- **Turnos de atendimento** (pronto): dia da semana, horário, intervalo, duração, vigência e frequência de cada profissional, em Usuários → Turnos.
- **Cadastro de pacientes** (pronto): feito pela equipe da clínica.
- **Prontuário** (pronto): avaliação fisioterapêutica completa (anamnese, estilo de vida, percepção do paciente, mapa da dor, sinais vitais, dermátomos e miótomos, testes especiais e questionários com pontuação automática, metas por semana com metas padrão) e evoluções, visível só para o profissional de saúde, com histórico de alterações, registro de acessos e relatório A4/PDF.
- **Paletas de cores** (pronto): o administrador escolhe as cores do sistema todo em Configurações → Geral, e cada usuário pode escolher outra só para ele em Conta.
- **Protocolos** (planejado): modelos de tratamento que podem ser adicionados ao paciente e às evoluções.

## Status

Base aprovada: o [Easy!Appointments](https://github.com/alextselegidis/easyappointments) 1.6.1, personalizado para fisioterapia (turnos, prontuário, textos e marca). O módulo de protocolos ainda será desenvolvido. As decisões e requisitos do projeto estão em [AGENTS.md](AGENTS.md).

## Ver o sistema funcionando (GitHub Codespaces)

Não precisa instalar nada no computador.

**Atalho:** [abrir no Codespaces](https://codespaces.new/KyreStoner/Agenda-Fisioterapia/tree/base-easyappointments?quickstart=1) (cria o ambiente na primeira vez e retoma o mesmo nas próximas).

1. Ou, no GitHub, abra a branch do projeto e clique em **Code → Codespaces → Create codespace**.
2. Aguarde a preparação. Na primeira vez leva alguns minutos, porque instala tudo e carrega dados **fictícios** de demonstração.
3. O sistema abre numa nova aba. Se não abrir, vá na aba **PORTAS** (Ports) e clique no endereço da porta **8000**.

- Agendamento do paciente: o endereço principal.
- Painel da clínica: acrescente `/index.php/login` ao endereço.

Logins **fictícios** de demonstração (só para o Codespace; nunca use em produção):

| Perfil | Usuário | Senha | O que vê |
| --- | --- | --- | --- |
| Administrador | `administrator` | `administrator` | Configurações, usuários, turnos, agenda e pacientes (sem prontuário) |
| Fisioterapeuta | `fisio` | `fisio1234` | Agenda, pacientes e **prontuário** |
| Recepcionista | `recepcao` | `recepcao1234` | Agenda e cadastro de pacientes (sem prontuário) |

### Atualizar um Codespace já criado

No **Terminal** do Codespace:

```bash
git pull origin claude/beautiful-carson-frxgwm
bash .devcontainer/start.sh
bash .devcontainer/demo-logins.sh
```

O `start.sh` aplica as alterações do banco, recompila JS e CSS e reinicia o servidor. O `demo-logins.sh` cria ou redefine os logins da tabela acima.

Para ver prontuários já preenchidos (pacientes fictícios Carlos Fictício, Mariana Exemplo e Beatriz Modelo, com avaliação, testes, metas e evoluções), rode também:

```bash
php .devcontainer/demo-records.php
```

Se a página abrir só com texto, sem cores e sem botões, ou mostrar **HTTP ERROR 404**, abra a aba **Terminal**, rode `bash .devcontainer/start.sh` e espere a mensagem **OK** (uns 30 segundos). Depois recarregue a página. Se o editor do Codespace mostrar que ele parou, reinicie por lá antes.

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
