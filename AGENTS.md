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
   - Configuração: nome e cor em Configurações → Geral; serviços com duração e **valor** em Serviços (o preço é por serviço/especialidade).
   - **Turnos** (Usuários → Turnos, pedido do usuário com base na tela de turnos que ele conhece, só como referência visual): cada turno tem dia da semana (ou "dias úteis"/"todos os dias"), horário inicial e final, intervalo, **duração** (passo da grade de horários), **vigência** e frequência (toda semana ou semana sim, semana não). Tabela `provider_shifts` (migração 071), model `Provider_shifts_model`, library `Shifts`, controller `Provider_shifts`.
     - Com turnos, a `Availability` usa a grade do turno (início + N × duração, recomeçando após o intervalo; o atendimento inteiro precisa caber num período livre). Exceções de horário de uma data têm prioridade. Sem turnos, vale o `working_plan` original.
     - Salvar turnos sincroniza o `working_plan` (usado pela agenda interna para sombrear horas sem atendimento). Excluir o último turno volta o profissional ao horário padrão da clínica.
     - Na ficha do profissional, a aba de horário semanal fica escondida (o `providers.js` ainda a lê) e aponta para os Turnos; as exceções de data continuam lá.
   - A validação de e-mail do agendamento é só de formato, por enquanto (o usuário não quer algo mais robusto agora).
2. **Cadastro de pacientes**: feito pela equipe que usa o sistema (profissional ou recepção).
   - Nomes na interface: **Paciente** (não "cliente"), **Profissional** (não "atendente"), **Recepcionista** (não "secretária"). Os textos do Google ("ID do Cliente") ficam como estão.
3. **Evolução clínica**: registro de cada atendimento no histórico do paciente.
   - Fluxo pedido pelo usuário: a fisioterapeuta entra no painel, abre o paciente que foi agendado pela agenda e cria ali a **avaliação** dele; depois registra as evoluções.
   - Implementado (migração 072, controller `Clinical_records`, model `Clinical_records_model`, views `clinical_record*.php`): botão "Prontuário" no popup da consulta e "Abrir prontuário" na tela de Pacientes; linha do tempo com avaliação e evoluções, evolução da dor, versões anteriores e registro de acessos.
   - Fichas definidas em `application/config/clinical_forms.php` (campos guardados em JSON). Modelo aprovado pelo usuário: anamnese, dor (escala 0 a 10), exame físico, diagnóstico fisioterapêutico, objetivos e plano; evolução com dor, condutas, resposta, intercorrências e orientações. Outras especialidades virão: crie novas fichas nesse arquivo.
   - **Acesso (decisão do usuário)**: só profissional de saúde vê o prontuário (permissão `clinical_records` nos perfis: provider = ver/criar/editar). Administrador e recepcionista **não** veem conteúdo clínico. A recepcionista vê o cadastro do paciente e a agenda dos profissionais liberados para ela (vínculo recepcionista → profissionais do próprio sistema).
   - Registros não são excluídos; só o autor edita, e cada edição guarda a versão anterior (`clinical_record_versions`). Todo acesso/criação/alteração vai para `clinical_access_log`. Paciente com prontuário não pode ser excluído (FK RESTRICT + mensagem no `Customers_model`). Atenção: a exclusão automática de dados pessoais (`data_retention_days`) não pode apagar quem tem prontuário.
   - Ainda não feito: impressão/PDF do prontuário, assinatura e número do CREFITO do profissional nos registros, tela de auditoria para o administrador.
4. **Protocolos**: modelos de tratamento/condutas que podem ser cadastrados e adicionados ao paciente ou à evolução.

## Dados sensíveis

- O sistema guarda dados de saúde, que são dados pessoais sensíveis pela **LGPD**. Prontuário de fisioterapia também segue as normas do **COFFITO** sobre registro e guarda.
- Exigências: login com níveis de acesso, registro de quem acessou ou alterou cada prontuário, conexão HTTPS e backups.
- Nunca coloque dados reais de pacientes, credenciais ou tokens no repositório, nos exemplos ou nos testes. Use dados fictícios.
  - Única exceção: os logins **fictícios** do ambiente de demonstração (`administrator`, `fisio`, `recepcao`, em `.devcontainer/`), que só existem no Codespace. Num servidor de verdade eles não podem existir.

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
- Os arquivos `.min.js` e `.css` de `assets/` são gerados pelo gulp e ignorados pelo git. O `start.sh` roda `npx gulp scripts styles` a cada início, então num Codespace já criado basta rodá-lo de novo. Mudanças em views, controllers e traduções valem na hora.
- As telas novas (Turnos, Prontuário) são renderizadas no servidor, com formulários POST e `csrf_token`, sem depender de JS compilado.
- Testes: `vendor/bin/phpunit`. O teste `TimezonesTest::testFlatArrayResolvesDeprecatedIdentifiers` já falhava antes das mudanças (depende da base de fusos do sistema).
- O menu do painel só fica aberto a partir de 1200px (`navbar-expand-xl`); abaixo disso recolhe no botão, para não haver rolagem lateral no tablet.
- Configuração local em `config.php` (cópia de `config-sample.php`, ignorada pelo git).
- Comandos de console: `php index.php console install|migrate|seed|backup`.
- Ambiente de demonstração para o usuário ver o sistema: GitHub Codespaces, configurado em `.devcontainer/`. O `setup.sh` instala tudo e carrega dados fictícios com `demo-data.sh` (que chama `demo-logins.sh`: logins fictícios `fisio`/`fisio1234` e `recepcao`/`recepcao1234`, só para a demonstração), e o `start.sh` aplica migrações, recompila assets e (re)inicia o servidor na porta 8000 com o `router.php`. O Codespaces repassa as requisições com `Host: localhost:8000` e o endereço público em `X-Forwarded-Host`; o `router.php` corrige isso para os links de CSS, JS e imagens não apontarem para localhost (sem ele a página abre sem estilos). Ao mudar dependências ou o processo de instalação, mantenha esses scripts funcionando.
- Os módulos novos (prontuário, evolução, protocolos) devem seguir esse mesmo padrão: migração + model + controller + view + JS + textos em `portuguese-br`.
- **Licença GPL-3.0 herdada**: o projeto inteiro passa a ser GPL-3.0. Mantenha os avisos de copyright e licença do código original e um aviso legal discreto no rodapé do painel. A marca e o visual podem ser personalizados.

## Pendências (decidir com o usuário)

- Hospedagem.
- Por enquanto **uma fisioterapeuta** e uma especialidade; outras especialidades virão (o sistema já aceita vários profissionais). Uma unidade ou várias?
- Lembretes de consulta (WhatsApp, e-mail)?
- O paciente terá login próprio ou só agenda com dados básicos? (Por enquanto o usuário aprovou o formulário atual: nome, sobrenome, e-mail, telefone, endereço, cidade, CEP e observações.)
- Protocolos de tratamento (módulo 4).
