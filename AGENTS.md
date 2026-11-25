# Memória do projeto

Instruções permanentes para qualquer assistente (Claude, Codex etc.) que trabalhe neste repositório.

## Identidade

- Nome **provisório**: **Sistema de Agendamento Fisioterapia**. O usuário vai trocar esse nome mais adiante.
  - Defina o nome em **um único lugar** (configuração ou constante, por exemplo `APP_NAME`) e use essa referência em todo o sistema: títulos, e-mails, manifest, metadados.
  - Não espalhe o nome fixo pelo código. Trocar o nome deve exigir uma única alteração.
  - Implementado: configuração `app_name` (migração 070), editável em **Configurações → Geral → Nome do sistema**. No código, use sempre o helper `app_name()`; o valor inicial fica em `application/config/app.php`.
  - O **nome da clínica** é outra coisa: `company_name` (Configurações → Geral → Nome da clínica), exibido ao paciente.
- **Cores (paletas)**: as paletas ficam em `application/config/color_palettes.php`: as 4 que o usuário mandou (Azul-marinho, Verde-oliva, Azul sereno, Vinho e coral), mais o verde original (`default`) e a cor única "Personalizada" (`custom`, que usa `company_color`).
  - O administrador escolhe em **Configurações → Geral → Paleta de cores** (`color_palette`, migração 075). Vale para o sistema todo: painel de todos os usuários, página do paciente, login e e-mails. A tela mostra uma prévia ao clicar; vale para todos depois de Salvar.
  - Cada usuário pode escolher outra **só para ele** em **Conta → Cores do sistema** (`user_settings.color_palette`; vazio = segue a clínica). Isso muda só o painel dele; a página do paciente segue a clínica.
  - Código: `application/helpers/palette_helper.php` (`active_color_palette()` no painel, `system_color_palette()` nas páginas públicas, `clinic_color()` nos e-mails). O CSS sai de `color_palette_css()` pelo componente `palette_style` (tag `<style id="palette-style">`, que a prévia troca via `color_palettes/preview`). Escolha na tela: componente `color_palette_picker` + `assets/js/components/color_palette_picker.js`.
  - Papel de cada cor: `primary` (botões, links, horário escolhido; texto branco por cima), `dark` (menu do painel, cabeçalho do agendamento, janelas), `accent` (item aberto do menu, item selecionado, dia de hoje), `light` (fundos suaves, etapa atual do agendamento), `background` opcional (fundo das páginas).
  - Nova paleta: só acrescentar um bloco no arquivo de configuração. Não renomeie ids em uso. Em CSS ou tela nova, não use cor fixa: use `var(--bs-primary)` e `var(--ea-palette-dark|accent|light|background)`.
  - O ícone padrão (`assets/img/logo.png`, verde) não muda com a paleta.
- **Sem referências ao Easy!Appointments nas telas públicas e nos e-mails** (o usuário pediu para tirar o "Powered by"). O crédito e a licença ficam só numa linha discreta no rodapé do painel e na página "Sobre".
- Repositório no GitHub: `KyreStoner/Agenda-Fisioterapia` (o nome do repositório também pode mudar junto com o nome do sistema).
- Este projeto é **independente do Tasy Plus** (`KyreStoner/Tasy-Plus`). Não misture código nem arquivos entre eles.
- Interface, textos e documentação em **português do Brasil**.
  - **Idioma travado em português** (pedido do usuário): `$config['language_selection'] = false` em `application/config/app.php` esconde os seletores de idioma e ignora o idioma do navegador e o `?language=` (ver `EA_Controller::configure_language` e `Localization::change_language`). **Gancho para o futuro**: para oferecer outros idiomas, ponha `true` e traduza antes as chaves criadas neste projeto (hoje só em `portuguese-br` e `english`).
- **Rodapé do painel** (pedido do usuário): sempre visível (sticky), pequeno; à esquerda nome do sistema + versão (`$config['app_version']` em `app.php`, aumente a cada entrega) e o crédito discreto da licença (some no celular); à direita "nome do usuário / perfil" (`role_*` nas traduções). Sem seletor de idioma.

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
   - **A fisioterapeuta também agenda na própria agenda** (lembrete do usuário): pelo botão "+" → Consulta no Calendário, o profissional só vê a si mesmo no formulário. O sistema avisa conflito de horário e permite continuar (encaixe). Mantenha isso funcionando ao mexer em permissões ou na agenda.
   - **Situações dos atendimentos = cores da agenda** (pedido do usuário, no estilo do Tasy; migração 077). Lista padrão, no caminho do atendimento: **Marcado** (toda consulta nova), **Encaixado**, **Confirmado**, **Aguardando** (a recepção registrou a chegada), **Em atendimento** (o profissional começou), **Executado** (terminou), **Faltou**, **Cancelado**. Na interface a palavra é "Situação" (não "Status").
     - Configuráveis em Configurações → Lógica de negócio → Situações dos atendimentos: nome e cor de cada uma (`appointment_status_options` = nomes na ordem; `appointment_status_colors` = nome → cor). Lista padrão, cores e dicas em `application/config/appointment_statuses.php`; funções em `application/helpers/appointment_status_helper.php`.
     - Na agenda, o fundo do atendimento é a cor da situação e a faixa da esquerda é a cor do serviço (`assets/js/utils/appointment_status.js`, usado pelas duas visões). Situação sem cor fica com a cor do serviço.
     - **Troca rápida**: no balão do atendimento, quem pode editar atendimentos (inclusive a recepcionista) muda a situação numa lista (`calendar/update_appointment_status`, com as mesmas checagens de alcance do salvamento: `authorize_event_write`). Não manda e-mail.
     - **Mudanças automáticas** (pelo nome, em `appointment_status_automation`): confirmar um encaixe (aviso de horário ocupado → Continuar) passa a consulta de Marcado para Encaixado; salvar um registro do prontuário aberto a partir da consulta passa para Executado (a não ser que esteja Faltou/Cancelado/Executado). Se a situação for renomeada, a automação não acontece (sem erro): ajuste o nome no config.
     - **Legenda** discreta em cima do rodapé, só na tela da agenda (componente `calendar_legend`, pelo `section('footer_top')` do `backend_footer`): as situações com as cores, a faixa do serviço, Indisponível, Período bloqueado e Fechado. No celular rola para o lado dentro da própria linha.
2. **Cadastro de pacientes**: feito pela equipe que usa o sistema (profissional ou recepção).
   - Nomes na interface: **Paciente** (não "cliente"), **Profissional** (não "atendente"), **Recepcionista** (não "secretária"). Os textos do Google ("ID do Cliente") ficam como estão.
3. **Evolução clínica**: registro de cada atendimento no histórico do paciente.
   - Fluxo pedido pelo usuário: a fisioterapeuta entra no painel, abre o paciente que foi agendado pela agenda e cria ali a **avaliação** dele; depois registra as evoluções.
   - Implementado (migração 072, controller `Clinical_records`, model `Clinical_records_model`, views `clinical_record*.php`): botão "Prontuário" no popup da consulta e "Abrir prontuário" na tela de Pacientes; linha do tempo com avaliação e evoluções, evolução da dor, versões anteriores e registro de acessos.
   - Fichas definidas em `application/config/clinical_forms.php` (campos guardados em JSON). Outras especialidades virão: crie novas fichas nesse arquivo. Campo removido da ficha continua aparecendo nos registros antigos ("Outros registros"); não reaproveite nome de campo antigo.
   - **Orientações da fisioterapeuta (cliente do usuário)**, já aplicadas na avaliação:
     - **Local da dor = bonequinho** (`body_map`, componente `components/clinical/body_map.php`, desenho SVG próprio) em 4 vistas: frente, costas, lado direito, lado esquerdo; várias regiões. "Características da dor" continua um campo de texto único. O bonequinho também está (opcional) na evolução.
     - **Testes especiais e questionários por região** (`region_tests`): as regiões marcadas mostram no Exame físico os testes (resultado negativo/positivo/não realizado) e questionários (pontuação) da área, um bloco por área + lado. Catálogo em `application/config/clinical_catalog.php` — é uma **lista inicial**: a fisioterapeuta vai mandar as fichas dela (inclusive o modelo de exame físico, que está semi-pronto) e o catálogo/ficha devem ser trocados por elas. Os ids do catálogo ficam salvos: não renomeie ids usados.
     - **Diagnóstico**: "Diagnóstico fisioterapêutico (CBDF)" (ela digita a partir da CBDF) + "Outros achados".
     - **Metas por semana no lugar do plano** (`goals`): semana, meta e como atingir; o plano segue as metas. Na evolução, `goals_check` marca as metas atingidas; o prontuário mostra "X de Y metas" com as datas de cada semana (ideia de motivação/"dopamina" pedida pelo usuário).
     - **Segunda rodada de orientações da fisioterapeuta** (aplicada):
       - Anamnese ampliada: cirurgias; **estilo de vida** (atividade física, tabagismo, álcool, histórico de câncer pessoal/familiar, acompanhamentos e tratamentos) e **percepção do paciente** (crenças e expectativas, sono, humor/ansiedade/estresse, acompanhamento psicológico ou com outros profissionais). Tipo `choice` para respostas rápidas (rótulos `cr_opt_<campo>_<opção>`).
       - **Sinais vitais** antes do Exame físico: PA (`blood_pressure`), FC, FR, SpO₂, temperatura, peso, altura (`measure`, aceita vírgula) e **IMC calculado** (`bmi`, com classificação da OMS). Funções em `application/helpers/clinical_helper.php`.
       - **Dermátomos e miótomos** (`neuro_screen`): níveis C4–T1 (membro superior/cervical) ou L2–S1 (membro inferior/lombar) conforme as regiões marcadas no bonequinho; sensibilidade e força 0–5, D e E; alterações em vermelho. Níveis em `clinical_catalog.php` (`neuro_levels`).
       - **Questionários com perguntas e pontuação automática**: definidos em `application/config/clinical_questionnaires.php` (perguntas, opções com pontos, `sum` ou `percent`). Com todas as perguntas respondidas, o servidor calcula a pontuação e guarda as respostas. **A fisioterapeuta vai mandar os questionários dela**; o `exemplo_demo` é só demonstração e deve ser apagado. Cuidado com direitos autorais dos questionários.
       - Testes especiais continuam sem valor pré-selecionado (decisão dela: assim ela vê o que fez e o que não fez).
       - **Metas padrão** (`goal_templates`, migração 074, tela `Goal_templates`, menu do usuário "Metas padrão", só para quem cria registros clínicos): metas gerais ou por área; na avaliação, cada meta tem uma lista "Escolher meta padrão" com as gerais e as das áreas marcadas, que preenche meta e "como atingir".
   - **Tipos de registro (tipos de evolução) com permissão por perfil** (pedido do usuário, migração 078): Configurações → Prontuário (`Clinical_record_types`, model `Clinical_record_types_model`). Cada tipo tem nome, ficha (`assessment`/`evolution` de `clinical_forms.php`), ordem e ativo; e, por perfil (Profissional, Administrador, Recepcionista), **Ver** e **Adicionar**. Quem adiciona também vê; editar = adicionar + ser o autor. Tipo com registros não é apagado, só desativado (desativado não recebe registros novos). Começa com "Avaliação inicial" e "Evolução de seguimento" (os registros antigos foram ligados a eles); tipo novo começa liberado para o profissional ver/adicionar e o administrador ver.
     - `clinical_records.id_clinical_record_types` diz o tipo; a coluna `type` continua dizendo a ficha usada (o registro antigo abre com a ficha dele mesmo se o tipo mudar de ficha). O prontuário, o relatório A4 e as metas usam só os registros dos tipos que o perfil vê; os botões de novo registro são os tipos em que ele pode adicionar.
     - A permissão geral `clinical_records` dos perfis (tabela roles) é recalculada a partir da tela (`sync_roles`): vê o prontuário quem vê algum tipo. Não edite essa permissão direto.
     - **Acesso hoje (decisão do usuário)**: profissional (qualquer especialidade) vê e adiciona; **administrador vê, só para leitura** (aviso "Somente leitura", sem criar/editar); **recepcionista não vê** ("por enquanto ainda não"). O administrador pode mudar isso na tela. Todo acesso vai para o histórico. A recepcionista vê o cadastro do paciente e a agenda dos profissionais liberados para ela (vínculo recepcionista → profissionais do próprio sistema).
   - Registros não são excluídos; só o autor edita, e cada edição guarda a versão anterior (`clinical_record_versions`). Todo acesso/criação/alteração vai para `clinical_access_log`. Paciente com prontuário não pode ser excluído (FK RESTRICT + mensagem no `Customers_model`). Atenção: a exclusão automática de dados pessoais (`data_retention_days`) não pode apagar quem tem prontuário.
   - **Relatório A4** (`clinical_records/report/{paciente}`, com `?record=ID` para um registro só; view `clinical_record_report.php`): folha A4 com cabeçalho da clínica, identificação do paciente, resumo das metas, registros em ordem cronológica e linha de assinatura com CREFITO. Botão "Imprimir / PDF" no prontuário e "Imprimir" em cada registro; o PDF sai pelo "Salvar como PDF" do navegador. Imprimir fica registrado no histórico de acessos (`print`). O conteúdo de cada registro vem do componente compartilhado `components/clinical/record_content.php` (o mesmo da tela).
   - Ainda não feito: questionários da fisioterapeuta (aguardando ela mandar; o motor já está pronto) e o restante do modelo de exame físico dela, número do CREFITO no cadastro do profissional (hoje o relatório deixa a linha em branco), tela de auditoria para o administrador.
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
- Ambiente de demonstração para o usuário ver o sistema: GitHub Codespaces, configurado em `.devcontainer/`. O `setup.sh` instala tudo e carrega dados fictícios com `demo-data.sh` (que chama `demo-logins.sh`: logins fictícios `fisio`/`fisio1234` e `recepcao`/`recepcao1234`, só para a demonstração; e `demo-records.php`: prontuários fictícios completos de Carlos, Mariana e Beatriz, que pode ser rodado de novo sem duplicar), e o `start.sh` aplica migrações, recompila assets, (re)inicia o servidor na porta 8000 com o `router.php` e termina dizendo **OK** ou **ERRO** (com as últimas linhas do log). Um 404 em branco no endereço `*.app.github.dev` vem do Codespaces, não do sistema (as páginas de erro do sistema têm conteúdo): servidor parado ou encaminhamento de porta travado depois que o Codespace religa. O `start.sh` tenta escutar em `[::]` (IPv4 + IPv6) e cai para `0.0.0.0` se o ambiente não tiver IPv6; no fim mostra o teste interno, o endereço público e um link `http://localhost:8000/login` que, clicado no terminal, faz o Codespaces refazer o encaminhamento. O Codespaces repassa as requisições com `Host: localhost:8000` e o endereço público em `X-Forwarded-Host`; o `router.php` corrige isso para os links de CSS, JS e imagens não apontarem para localhost (sem ele a página abre sem estilos). Ao mudar dependências ou o processo de instalação, mantenha esses scripts funcionando.
- Os módulos novos (prontuário, evolução, protocolos) devem seguir esse mesmo padrão: migração + model + controller + view + JS + textos em `portuguese-br`.
- **Licença GPL-3.0 herdada**: o projeto inteiro passa a ser GPL-3.0. Mantenha os avisos de copyright e licença do código original e um aviso legal discreto no rodapé do painel. A marca e o visual podem ser personalizados.

## Pendências (decidir com o usuário)

- Hospedagem.
- Por enquanto **uma fisioterapeuta** e uma especialidade; outras especialidades virão (o sistema já aceita vários profissionais). Uma unidade ou várias?
- Lembretes de consulta (WhatsApp, e-mail)?
- O paciente terá login próprio ou só agenda com dados básicos? (Por enquanto o usuário aprovou o formulário atual: nome, sobrenome, e-mail, telefone, endereço, cidade, CEP e observações.)
- Protocolos de tratamento (módulo 4).
- Tipos de registro: feitos por perfil (decisão do usuário). Ainda não feito: liberação por usuário, regras por tipo ("uma vez por tratamento", "exige avaliação antes"), fichas próprias para outros tipos (alta, por exemplo) e o agrupamento por tratamento (avaliação → evoluções → alta).
