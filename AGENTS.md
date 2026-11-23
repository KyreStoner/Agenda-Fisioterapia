# Memória do projeto

Instruções permanentes para qualquer assistente (Claude, Codex etc.) que trabalhe neste repositório.

## Identidade

- Nome **provisório**: **Sistema de Agendamento Fisioterapia**. O usuário vai trocar esse nome mais adiante.
  - Defina o nome em **um único lugar** (configuração ou constante, por exemplo `APP_NAME`) e use essa referência em todo o sistema: títulos, e-mails, manifest, metadados.
  - Não espalhe o nome fixo pelo código. Trocar o nome deve exigir uma única alteração.
  - Implementado: configuração `app_name` (migração 070), editável em **Configurações → Geral → Nome do sistema**. No código, use sempre o helper `app_name()`; o valor inicial fica em `application/config/app.php`.
  - O **nome da clínica** é outra coisa: `company_name` (Configurações → Geral → Nome da clínica), exibido ao paciente.
- **Cor**: por enquanto fica o verde padrão. O usuário troca em **Configurações → Geral → Cor principal** (`company_color`), que vale para a página do paciente e para o painel.
- **Sem referências ao Easy!Appointments nas telas públicas e nos e-mails** (o usuário pediu para tirar o "Powered by"). O crédito e a licença ficam só numa linha discreta no rodapé do painel e na página "Sobre".
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
   - Configuração (aprovada pelo usuário, já existe no painel): nome e cor em Configurações → Geral; serviços com duração e **valor** em Serviços (o preço é por serviço/especialidade); horário de cada fisioterapeuta por dia da semana, com pausas (almoço) e exceções, em Usuários → Atendentes → Horário de atendimento. O que é configurado ali reflete direto nos horários que o paciente vê.
   - A validação de e-mail do agendamento é só de formato, por enquanto (o usuário não quer algo mais robusto agora).
2. **Cadastro de pacientes**: feito pela equipe que usa o sistema (profissional ou recepção).
3. **Evolução clínica**: registro de cada atendimento no histórico do paciente.
   - Fluxo pedido pelo usuário: a fisioterapeuta entra no painel, abre o paciente que foi agendado pela agenda e cria ali a **avaliação** dele; depois registra as evoluções.
4. **Protocolos**: modelos de tratamento/condutas que podem ser cadastrados e adicionados ao paciente ou à evolução.

## Dados sensíveis

- O sistema guarda dados de saúde, que são dados pessoais sensíveis pela **LGPD**. Prontuário de fisioterapia também segue as normas do **COFFITO** sobre registro e guarda.
- Exigências: login com níveis de acesso, registro de quem acessou ou alterou cada prontuário, conexão HTTPS e backups.
- Nunca coloque dados reais de pacientes, credenciais ou tokens no repositório, nos exemplos ou nos testes. Use dados fictícios.

## Base técnica: Easy!Appointments (aprovada pelo usuário)

- Aprovado como base do agendamento, a partir da branch `base-easyappointments`: o [Easy!Appointments](https://github.com/alextselegidis/easyappointments) 1.6.1 como base do agendamento.
- Stack: PHP 8.2+, CodeIgniter 3 (embutido em `system/`, que é código do framework e **não deve ser editado**), MySQL/MariaDB, Bootstrap e jQuery, com build de assets por gulp.
- Estrutura principal:
  - `application/controllers`: páginas, com a API REST em `api/v1`.
  - `application/models`: uma model por entidade.
  - `application/libraries`: regras de negócio, como disponibilidade e notificações.
  - `application/migrations`: alterações do banco. Sempre crie a próxima migração numerada e nunca edite uma existente.
  - `application/language/portuguese-br`: textos em português.
  - `application/views` e `assets/`: telas, JS e SCSS.
- Os arquivos `.min.js` e `.css` de `assets/` são gerados pelo gulp e ignorados pelo git. Mudanças em `assets/js` ou `assets/css/*.scss` só aparecem depois de `npx gulp compile` (inclusive num Codespace já criado). Mudanças em views, controllers e traduções valem na hora.
- O menu do painel só fica aberto a partir de 1200px (`navbar-expand-xl`); abaixo disso recolhe no botão, para não haver rolagem lateral no tablet.
- Configuração local em `config.php` (cópia de `config-sample.php`, ignorada pelo git).
- Comandos de console: `php index.php console install|migrate|seed|backup`.
- Ambiente de demonstração para o usuário ver o sistema: GitHub Codespaces, configurado em `.devcontainer/`. O `setup.sh` instala tudo e carrega dados fictícios com `demo-data.sh`, e o `start.sh` (re)inicia o servidor na porta 8000 com o `router.php`. O Codespaces repassa as requisições com `Host: localhost:8000` e o endereço público em `X-Forwarded-Host`; o `router.php` corrige isso para os links de CSS, JS e imagens não apontarem para localhost (sem ele a página abre sem estilos). Ao mudar dependências ou o processo de instalação, mantenha esses scripts funcionando.
- Os módulos novos (prontuário, evolução, protocolos) devem seguir esse mesmo padrão: migração + model + controller + view + JS + textos em `portuguese-br`.
- **Licença GPL-3.0 herdada**: o projeto inteiro passa a ser GPL-3.0. Mantenha os avisos de copyright e licença do código original e um aviso legal discreto no rodapé do painel. A marca e o visual podem ser personalizados.

## Pendências (decidir com o usuário)

- Hospedagem.
- Um profissional ou vários? Uma unidade ou várias?
- Lembretes de consulta (WhatsApp, e-mail)?
- O paciente terá login próprio ou só agenda com dados básicos? (Por enquanto o usuário aprovou o formulário atual: nome, sobrenome, e-mail, telefone, endereço, cidade, CEP e observações.)
- Campos da avaliação e da evolução fisioterapêutica (definir com o usuário antes de construir o módulo).
