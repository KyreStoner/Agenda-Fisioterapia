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

## Pendências (decidir com o usuário)

- Projeto de referência no GitHub que o usuário quer usar como base: aguardando o link.
- Tecnologia (stack) e hospedagem.
- Um profissional ou vários? Uma unidade ou várias?
- Lembretes de consulta (WhatsApp, e-mail)?
- O paciente terá login próprio ou só agenda com dados básicos?
