# Memória do projeto

Instruções permanentes para qualquer assistente (Claude, Codex etc.) que trabalhe neste repositório.

## Identidade

- Nome **provisório**: **Sistema de Agendamento Fisioterapia**. O usuário vai trocar esse nome mais adiante.
  - Defina o nome em **um único lugar** (configuração ou constante, por exemplo `APP_NAME`) e use essa referência em todo o sistema: títulos, e-mails, manifest, metadados.
  - Não espalhe o nome fixo pelo código. Trocar o nome deve exigir uma única alteração.
- Repositório no GitHub: `KyreStoner/Agenda-Fisioterapia` (o nome do repositório também pode mudar junto com o nome do sistema).
- Este projeto é **independente do Tasy Plus** (`KyreStoner/Tasy-Plus`). Não misture código nem arquivos entre eles.
- Interface, textos e documentação em **português do Brasil**.

## Requisito obrigatório: tudo responsivo

- O sistema precisa funcionar bem **no navegador do computador e no celular**.
- O **paciente agenda pelo celular** e vê os horários disponíveis. A área pública de agendamento deve ser pensada **mobile-first**.
- Toda tela nova ou alterada precisa ser verificada nos tamanhos de celular (~360px), tablet e desktop, sem rolagem horizontal, com botões e campos fáceis de tocar.

## Módulos planejados

1. **Agendamento**
   - Área pública do paciente: ver horários disponíveis e marcar consulta.
   - Área interna: agenda da clínica (visualizar, confirmar, remarcar, cancelar).
2. **Cadastro de pacientes**: feito pela equipe que usa o sistema (profissional ou recepção).
3. **Evolução clínica**: registro de cada atendimento no histórico do paciente.
4. **Protocolos**: modelos de tratamento/condutas que podem ser cadastrados e adicionados ao paciente ou à evolução.

## Dados sensíveis

- O sistema guarda dados de saúde, que são dados pessoais sensíveis pela **LGPD**. Prontuário de fisioterapia também segue as normas do **COFFITO** sobre registro e guarda.
- Exigências: login com níveis de acesso, registro de quem acessou ou alterou cada prontuário, conexão HTTPS e backups.
- Nunca coloque dados reais de pacientes, credenciais ou tokens no repositório, nos exemplos ou nos testes. Use dados fictícios.

## Base técnica: Easy!Appointments (aguardando aprovação do usuário)

- Proposta em avaliação na branch `base-easyappointments`: usar o [Easy!Appointments](https://github.com/alextselegidis/easyappointments) 1.6.1 como base do agendamento.
- Stack: PHP 8.2+, CodeIgniter 3 (embutido em `system/`, que é código do framework e **não deve ser editado**), MySQL/MariaDB, Bootstrap e jQuery, com build de assets por gulp.
- Estrutura principal:
  - `application/controllers`: páginas, com a API REST em `api/v1`.
  - `application/models`: uma model por entidade.
  - `application/libraries`: regras de negócio, como disponibilidade e notificações.
  - `application/migrations`: alterações do banco. Sempre crie a próxima migração numerada e nunca edite uma existente.
  - `application/language/portuguese-br`: textos em português.
  - `application/views` e `assets/`: telas, JS e SCSS.
- Configuração local em `config.php` (cópia de `config-sample.php`, ignorada pelo git).
- Comandos de console: `php index.php console install|migrate|seed|backup`.
- Os módulos novos (prontuário, evolução, protocolos) devem seguir esse mesmo padrão: migração + model + controller + view + JS + textos em `portuguese-br`.
- **Licença GPL-3.0 herdada**: o projeto inteiro passa a ser GPL-3.0. Mantenha os avisos de copyright e licença do código original e um aviso legal discreto no rodapé do painel. A marca e o visual podem ser personalizados.

## Pendências (decidir com o usuário)

- Aprovar ou não a base Easy!Appointments (branch `base-easyappointments`).
- Hospedagem.
- Um profissional ou vários? Uma unidade ou várias?
- Lembretes de consulta (WhatsApp, e-mail)?
- O paciente terá login próprio ou só agenda com dados básicos?
