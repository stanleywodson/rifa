# Plano Técnico de Implementação — SaaS de Campanhas e Rifas

**Versão:** 1.1  
**Prazo:** 4 semanas (20 dias úteis)  
**Equipe:** 1 desenvolvedor  
**Modelo:** SaaS multiempresa (multi-tenant)  
**Monetização:** assinatura recorrente dos organizadores  
**Stack principal:** Laravel + Bootstrap  
**Banco de dados:** PostgreSQL (recomendado)  
**Status:** planejamento do MVP

---

## 1. Objetivo

Construir um MVP de uma plataforma SaaS na qual vários organizadores possam criar e gerenciar campanhas por meio de painéis isolados, enquanto o administrador da plataforma controla organizadores, planos, assinaturas e aprovação de campanhas.

O prazo de quatro semanas exige escopo controlado e uso de componentes consolidados. O MVP prioriza autenticação, isolamento multi-tenant, gestão de campanhas, planos, assinaturas e operação segura. Aplicativos nativos, marketplace avançado, afiliados, microserviços e analytics avançado ficam fora da primeira versão.

> **Bloqueio jurídico:** nenhuma modalidade de sorteio, venda de números, cobrança de participantes ou distribuição de prêmios deve ser habilitada antes de uma validação jurídica brasileira documentada e da obtenção das autorizações aplicáveis. Até lá, o sistema deve operar em modo de demonstração/gestão de rascunhos, sem transações de participantes relacionadas a premiações.

## 2. Validação jurídica obrigatória — Sprint 0

A validação jurídica é a primeira etapa e uma condição para habilitar funcionalidades reguladas. A legislação brasileira diferencia modalidades de promoção comercial, operações filantrópicas e outras atividades com regras próprias. Não se deve presumir que uma rifa paga esteja autorizada por ser apresentada como promoção comercial.

### Tarefas

| ID | Tarefa | Responsável | Estimativa |
|---|---|---|---:|
| LEG-01 | Descrever o modelo comercial pretendido | Produto/desenvolvedor | 2 h |
| LEG-02 | Consultar advogado com experiência regulatória | Contratação externa | Prazo externo |
| LEG-03 | Classificar a modalidade de campanha | Jurídico | Prazo externo |
| LEG-04 | Identificar autorizações, documentos e responsabilidades | Jurídico | Prazo externo |
| LEG-05 | Definir políticas de publicação e bloqueio | Jurídico + desenvolvedor | 2 h |
| LEG-06 | Registrar parecer e decisão de habilitação | Desenvolvedor | 1 h |

Honorários jurídicos e tempo de análise por órgãos públicos não estão incluídos na estimativa de desenvolvimento.

### Critérios de aceite

- Há parecer/orientação jurídica documentada sobre a modalidade pretendida.
- Está definido quem responde legalmente por cada campanha.
- Os requisitos de autorização e documentação estão registrados.
- O sistema registra e consulta o estado de validação.
- Enquanto não houver aprovação, o organizador pode criar rascunhos, mas não publicar campanhas reguladas nem habilitar vendas, pagamentos ou sorteios.

Referências oficiais para iniciar a análise:
- Ministério da Fazenda — Promoção Comercial: https://www.gov.br/fazenda/pt-br/composicao/orgaos/secretaria-de-premios-e-apostas/promocao-comercial
- Sistema de Controle de Promoções Comerciais (SCPC): https://www.gov.br/fazenda/pt-br/composicao/orgaos/secretaria-de-premios-e-apostas/promocao-comercial/scpc

## 3. Stack tecnológica

### Stack principal

| Camada | Tecnologia | Uso |
|---|---|---|
| Aplicação web | Laravel + Blade | Rotas, controllers, regras de negócio e renderização |
| Interface | Bootstrap 5 | Layout responsivo, formulários, tabelas, modais e componentes |
| Interatividade | JavaScript simples / Alpine.js opcional | Interações leves sem SPA completa |
| Banco de dados | PostgreSQL | Transações, integridade e dados multi-tenant |
| Autenticação | Laravel Breeze com Blade | Login, logout, recuperação de senha e verificação de e-mail |
| Autorização | Policies e Gates do Laravel | Controle de acesso por função e recurso |
| Filas | Driver `database` no MVP | Jobs e tarefas assíncronas simples |
| Arquivos | S3 compatível em produção | Imagens e documentos privados |
| Assinaturas | Provedor com cobrança recorrente no Brasil | Cobrança do organizador pelo uso do SaaS |
| Infraestrutura | Docker + Nginx + HTTPS | Execução e deploy |
| Testes | PHPUnit ou Pest | Testes unitários e de integração |
| CI/CD | GitHub Actions ou GitLab CI | Testes e deploy controlado |

### Decisões para manter o prazo

- Usar Laravel com Blade e Bootstrap, evitando uma API separada e um frontend Next.js no MVP.
- Utilizar Laravel Breeze com Blade para acelerar a autenticação.
- Manter a aplicação como monólito modular; não criar microserviços.
- Utilizar PostgreSQL e migrations do Laravel.
- Usar o driver de filas `database` inicialmente; adotar Redis/Horizon apenas se houver necessidade real.
- Utilizar checkout hospedado por um provedor de assinatura, depois de validar contrato, taxas e compatibilidade.
- Armazenar documentos privados fora da pasta pública e servir os arquivos mediante autorização.

### Arquitetura lógica

```text
Navegador
   |
   | HTTPS
   v
Nginx
   |
   v
Laravel (Blade + Bootstrap)
   |-- Autenticação (Breeze)
   |-- Tenant Context
   |-- Policies / Gates
   |-- Módulo de campanhas
   |-- Módulo de planos e assinaturas
   |-- Módulo de análise jurídica
   |-- Auditoria e notificações
   |
   +------ PostgreSQL
   |
   +------ Storage privado (S3 compatível)
   |
   +------ Provedor de cobrança da assinatura SaaS
               |
               +---- Webhook validado para Laravel
```

## 4. Escopo funcional do MVP

### Incluído

1. Cadastro, autenticação, logout e recuperação de senha.
2. Criação de organizador (tenant) e associação de usuários.
3. Papéis e permissões básicas.
4. Isolamento de dados entre organizadores.
5. CRUD de campanhas e controle de estados.
6. Upload de imagens e documentos.
7. Página pública para campanhas publicadas e elegíveis.
8. Cadastro de planos e limites.
9. Controle de assinaturas dos organizadores.
10. Painel do superadministrador.
11. Fluxo de análise documental e bloqueio jurídico.
12. Auditoria básica.
13. Testes essenciais, deploy, backup e documentação.

### Condicionado à validação jurídica

- Venda de números ou participações.
- Reserva de números pagos.
- Cobrança de participantes.
- Apuração de ganhadores e sorteios.
- Distribuição de prêmios e repasses financeiros.

### Fora do escopo das quatro semanas

- Aplicativos nativos.
- Marketplace e programa de afiliados.
- Vários provedores de pagamento.
- Microserviços e Kubernetes.
- Analytics avançado.
- Automação complexa de marketing.

## 5. Requisitos funcionais

| ID | Requisito | Prioridade |
|---|---|---|
| RF-01 | Cadastro, login, logout e recuperação de senha | P0 |
| RF-02 | Criar organizador e vincular usuários ao tenant | P0 |
| RF-03 | Impedir acesso cruzado entre tenants | P0 |
| RF-04 | Criar, editar, listar e arquivar campanhas | P0 |
| RF-05 | Controlar estados e publicação das campanhas | P0 |
| RF-06 | Registrar validação documental e jurídica | P0 |
| RF-07 | Exibir páginas públicas de campanhas elegíveis | P0 |
| RF-08 | Criar planos e limites de utilização | P0 |
| RF-09 | Controlar status das assinaturas | P0 |
| RF-10 | Bloquear recursos incompatíveis com o plano | P0 |
| RF-11 | Disponibilizar painel de superadministrador | P0 |
| RF-12 | Registrar auditoria de ações importantes | P1 |
| RF-13 | Enviar notificações essenciais | P1 |
| RF-14 | Disponibilizar relatórios básicos | P1 |
| RF-15 | Integrar cobrança recorrente do SaaS | P1 |
| RF-16 | Habilitar vendas/sorteios apenas para modalidade validada | Bloqueio jurídico |

### Requisitos não funcionais

- RNF-01: toda ação autenticada verifica identidade e autorização.
- RNF-02: consultas e comandos respeitam o tenant ativo.
- RNF-03: operações críticas utilizam transações de banco.
- RNF-04: endpoints e formulários sensíveis têm proteção contra abuso.
- RNF-05: erros não expõem stack traces, segredos ou dados de outros clientes.
- RNF-06: ações administrativas relevantes são rastreáveis.
- RNF-07: backups e procedimento de restauração são documentados e testados.
- RNF-08: páginas públicas possuem metadados SEO básicos.
- RNF-09: webhooks são autenticados e processados de forma idempotente.
- RNF-10: dados pessoais e documentos têm acesso controlado e política de retenção.

## 6. Backlog por sprint

Planejamento de 20 dias úteis. A capacidade estimada é de aproximadamente 120 horas de desenvolvimento, considerando cerca de 6 horas produtivas por dia. As estimativas são aproximadas; a análise jurídica e eventuais autorizações externas não estão incluídas.

### Sprint 1 — Fundação, validação e multi-tenancy (dias 1–5)

**Objetivo:** aplicação executável, autenticação e isolamento de organizadores.

| ID | Atividade | Estimativa |
|---|---|---:|
| S1-01 | Descrever modelo e iniciar validação jurídica | 4 h + prazo externo |
| S1-02 | Criar repositório e ambiente Docker | 3 h |
| S1-03 | Configurar Laravel, PostgreSQL e Bootstrap | 4 h |
| S1-04 | Implementar migrations iniciais | 4 h |
| S1-05 | Autenticação com Breeze/Blade | 5 h |
| S1-06 | Tenants e vínculo usuário-organizador | 5 h |
| S1-07 | Policies, contexto do tenant e testes de isolamento | 5 h |
| S1-08 | CI inicial e documentação de execução | 2 h |

**Entrega:** aplicação executável, login funcional, tenants isolados e bloqueio jurídico formalmente implementado.

### Sprint 2 — Campanhas e páginas públicas (dias 6–10)

**Objetivo:** permitir que organizadores gerenciem campanhas em rascunho e publiquem apenas campanhas elegíveis.

| ID | Atividade | Estimativa |
|---|---|---:|
| S2-01 | CRUD de campanhas e validação | 5 h |
| S2-02 | Estados e regras de publicação | 4 h |
| S2-03 | Upload e validação de imagens/documentos | 3 h |
| S2-04 | Listagem, filtros e paginação | 3 h |
| S2-05 | Layout Bootstrap do painel | 5 h |
| S2-06 | Formulário de campanha | 4 h |
| S2-07 | Página pública e SEO básico | 4 h |
| S2-08 | Testes de autorização e integração | 3 h |

**Entrega:** CRUD funcional, painel do organizador e páginas públicas com publicação protegida.

### Sprint 3 — Planos, assinaturas e administração (dias 11–15)

**Objetivo:** implementar monetização recorrente do SaaS e controles administrativos.

| ID | Atividade | Estimativa |
|---|---|---:|
| S3-01 | CRUD administrativo de planos | 4 h |
| S3-02 | Assinaturas e regras de acesso | 5 h |
| S3-03 | Limites por plano | 4 h |
| S3-04 | Checkout hospedado para assinatura | 5 h |
| S3-05 | Webhooks e idempotência | 5 h |
| S3-06 | Painel de superadministrador | 4 h |
| S3-07 | Fluxo de análise documental | 3 h |
| S3-08 | Testes de estados de assinatura | 3 h |

**Entrega:** planos configuráveis, limites aplicados, fluxo de assinatura e painel administrativo. Se a integração externa atrasar, utilizar ativação manual controlada no piloto e postergar o checkout.

### Sprint 4 — Auditoria, testes, segurança e deploy (dias 16–20)

**Objetivo:** estabilizar o MVP e preparar um piloto controlado.

| ID | Atividade | Estimativa |
|---|---|---:|
| S4-01 | Auditoria de ações críticas | 3 h |
| S4-02 | Relatórios básicos | 3 h |
| S4-03 | Notificações essenciais | 2 h |
| S4-04 | Testes funcionais e regressão | 5 h |
| S4-05 | Testes de isolamento e segurança | 4 h |
| S4-06 | Backup e restauração | 2 h |
| S4-07 | Deploy, HTTPS, variáveis e monitoramento | 4 h |
| S4-08 | Correções, documentação e checklist final | 5 h |

**Entrega:** MVP homologado e preparado para piloto, mantendo bloqueadas as funcionalidades reguladas que não tenham sido autorizadas.

### Controle do prazo

As tarefas somam aproximadamente 115 horas, com uma pequena margem para imprevistos. Para preservar as quatro semanas:

1. Priorizar isolamento multi-tenant, autenticação e bloqueio jurídico.
2. Usar componentes Bootstrap prontos.
3. Criar testes durante cada sprint.
4. Não implementar pagamentos de participantes no MVP.
5. Cortar primeiro relatórios e notificações não essenciais, nunca segurança ou validação jurídica.

## 7. Modelagem do banco de dados e migrations

A ordem sugerida para as migrations é:

| Ordem | Migration | Campos principais |
|---:|---|---|
| 1 | `create_users_table` | `id`, `name`, `email`, `password`, `email_verified_at` |
| 2 | `create_tenants_table` | `id`, `name`, `slug`, `status`, `settings` |
| 3 | `create_tenant_users_table` | `tenant_id`, `user_id`, `role`, `status` |
| 4 | `create_plans_table` | `id`, `name`, `code`, `price`, `billing_interval`, `limits`, `is_active` |
| 5 | `create_subscriptions_table` | `tenant_id`, `plan_id`, `provider`, `provider_subscription_id`, `status`, `current_period_end` |
| 6 | `create_campaigns_table` | `tenant_id`, `title`, `slug`, `description`, `status`, `starts_at`, `ends_at`, `published_at` |
| 7 | `create_campaign_compliance_table` | `campaign_id`, `review_status`, `legal_basis`, `authorization_number`, `reviewed_by`, `reviewed_at` |
| 8 | `create_campaign_documents_table` | `campaign_id`, `document_type`, `storage_key`, `status`, `expires_at` |
| 9 | `create_audit_logs_table` | `tenant_id`, `actor_id`, `action`, `subject_type`, `subject_id`, `metadata` |
| 10 | `create_webhook_events_table` | `provider`, `event_id`, `event_type`, `payload_hash`, `processed_at`, `status` |

O Laravel já fornece migrations para usuários, redefinição de senha e sessões conforme o starter kit escolhido. Adaptar a lista ao projeto existente para evitar duplicações.

### Regras de modelagem

**Tenants**
- `id`: UUID recomendado.
- `slug`: único globalmente.
- `status`: `pending`, `active`, `suspended`, `closed`.
- `settings`: JSON para configurações não críticas.

**Tenant users**
- Chaves estrangeiras para `tenant_id` e `user_id`.
- Papéis: `owner`, `admin`, `editor`, `viewer`.
- Restrição única em `(tenant_id, user_id)`.

**Plans**
- `code` único.
- Armazenar preço de maneira consistente: inteiro em centavos ou decimal com precisão definida.
- `billing_interval`: `monthly` ou `yearly`.
- `limits`: JSON validado no backend.
- Desativar planos sem apagar o histórico de assinaturas.

**Subscriptions**
- Pertencem ao tenant, não ao usuário individual.
- Status: `pending`, `trialing`, `active`, `past_due`, `canceled`, `expired`.
- Índices para IDs externos e datas de ciclo.
- Tratar transições de plano e eventos duplicados em transação.

**Campaigns**
- `tenant_id` obrigatório e indexado.
- Índice composto único `(tenant_id, slug)`.
- Status: `draft`, `pending_review`, `approved`, `published`, `paused`, `completed`, `rejected`, `archived`.
- A publicação só pode ocorrer após validações no servidor.

**Campaign compliance**
- Registrar modalidade analisada, responsável, data, decisão e fundamento.
- Guardar referências a documentos privados, não caminhos públicos previsíveis.
- Registrar número de autorização e validade quando aplicáveis.

**Audit logs**
- Registrar ator, tenant, ação, entidade, horário e metadados mínimos.
- Nunca registrar senhas, tokens ou dados pessoais desnecessários.
- Impedir edição dos registros por usuários comuns.

Migrations adicionais futuras: `tenant_invitations`, `subscription_events`, `notifications` e `campaign_reviews`.

### Assinaturas

A assinatura pertence ao tenant para permitir múltiplos colaboradores em uma mesma organização. No MVP, adotar uma assinatura ativa por tenant, com tratamento transacional de troca de plano e cancelamento.

## 8. Endpoints da aplicação

Como o MVP usa Laravel com Blade, a maior parte das operações pode ser implementada com rotas web e formulários protegidos por CSRF. Os endpoints JSON ficam reservados para integrações, webhooks e eventuais usos futuros.

### Rotas web

| Método | Rota | Acesso | Objetivo |
|---|---|---|---|
| `GET` | `/dashboard` | Autenticado | Painel do organizador |
| `GET` | `/tenant/settings` | Owner/admin | Configurações do organizador |
| `PATCH` | `/tenant/settings` | Owner/admin | Atualizar organizador |
| `GET` | `/tenant/campaigns` | Membro do tenant | Listar campanhas |
| `GET` | `/tenant/campaigns/create` | Editor/admin | Formulário de criação |
| `POST` | `/tenant/campaigns` | Editor/admin | Criar campanha |
| `GET` | `/tenant/campaigns/{campaign}/edit` | Editor/admin | Formulário de edição |
| `PATCH` | `/tenant/campaigns/{campaign}` | Editor/admin | Atualizar campanha |
| `POST` | `/tenant/campaigns/{campaign}/submit-review` | Owner/admin | Solicitar análise |
| `POST` | `/tenant/campaigns/{campaign}/publish` | Owner/admin | Publicar se elegível |
| `POST` | `/tenant/campaigns/{campaign}/archive` | Owner/admin | Arquivar |
| `GET` | `/campaigns/{tenantSlug}/{campaignSlug}` | Pública | Página pública elegível |
| `GET` | `/tenant/subscription` | Membro do tenant | Consultar assinatura |
| `POST` | `/tenant/subscription/checkout` | Owner/admin | Iniciar checkout |
| `POST` | `/tenant/subscription/cancel` | Owner/admin | Solicitar cancelamento |
| `GET` | `/admin/tenants` | Superadmin | Listar organizadores |
| `PATCH` | `/admin/tenants/{tenant}/status` | Superadmin | Suspender/reativar |
| `GET` | `/admin/plans` | Superadmin | Listar planos |
| `POST` | `/admin/plans` | Superadmin | Criar plano |
| `PATCH` | `/admin/plans/{plan}` | Superadmin | Atualizar plano |
| `GET` | `/admin/campaign-reviews` | Revisor | Listar campanhas pendentes |
| `POST` | `/admin/campaigns/{campaign}/review` | Revisor | Registrar decisão |

### Rotas de integração

| Método | Endpoint | Objetivo |
|---|---|---|
| `POST` | `/webhooks/subscriptions/{provider}` | Receber eventos de cobrança da assinatura SaaS |
| `GET` | `/api/v1/me` | Informações do usuário autenticado, se necessária API |
| `GET` | `/api/v1/public/campaigns` | Catálogo público, se necessária API |

Não é necessário construir uma API REST completa no MVP Blade. As rotas web do Laravel reduzem trabalho e simplificam sessão, CSRF e validação.

### Segurança das rotas

- Utilizar middleware `auth` nas rotas privadas.
- Utilizar Policies para cada campanha e recurso do tenant.
- Verificar o papel de superadministrador por mecanismo separado dos papéis de tenant.
- Validar CSRF em formulários web.
- Proteger webhooks por assinatura/segredo e idempotência.
- Não confiar em `tenant_id` recebido do navegador.
- Aplicar rate limiting a login, formulários públicos e integrações.

## 9. Critérios de aceite

### CA-01 — Cadastro e isolamento
- Usuário cria conta e organizador.
- Um usuário sem vínculo não acessa dados de outro tenant.
- Alterar IDs na URL não permite consultar ou modificar dados alheios.
- O contexto do tenant é resolvido no servidor.

### CA-02 — Campanhas
- O organizador cria e edita seus rascunhos.
- Campos obrigatórios são validados no backend.
- A publicação exige estado elegível e validação documental aplicável.
- Campanhas suspensas ou não elegíveis não aparecem no catálogo público.

### CA-03 — Assinatura SaaS
- O organizador consulta plano e status.
- Limites são aplicados no backend.
- Webhooks repetidos não duplicam alterações.
- Assinaturas vencidas ou inadimplentes seguem política documentada.

### CA-04 — Validação jurídica
- Campanhas reguladas não são publicadas com análise pendente.
- Aprovação registra responsável, data, fundamento e documentos.
- Documentos vencidos ou campanhas suspensas são tratados corretamente.
- Nenhuma rota alternativa contorna o bloqueio.

### CA-05 — Operação
- Logs não expõem segredos.
- Backup e restauração foram testados.
- Produção utiliza HTTPS e segredos fora do repositório.
- Existe procedimento para suspender tenant e revogar acessos.

## 10. Estratégia de testes

| Camada | Ferramenta | Escopo |
|---|---|---|
| Unitários | PHPUnit ou Pest | Regras de negócio e transições |
| Integração | Laravel Feature Tests | Rotas, banco, Policies e isolamento |
| Interface | Testes manuais e, se possível, Playwright | Fluxos críticos do navegador |
| Segurança | Testes próprios e revisão de configuração | Acesso cruzado e exposição de dados |
| Infraestrutura | Docker Compose e scripts | Deploy, backup e restauração |

Casos obrigatórios:
1. Tenant A não consulta, altera ou exclui recursos do tenant B.
2. Editor não altera assinatura.
3. Usuário comum não acessa painel administrativo.
4. Campanha pendente não pode ser publicada.
5. Assinatura inativa não libera recursos pagos.
6. Webhooks duplicados não geram estados inconsistentes.
7. Limites do plano são aplicados no servidor.
8. Documentos privados não têm acesso público direto.
9. Campanha suspensa deixa de aparecer publicamente.
10. Backup restaurado contém os dados essenciais.

## 11. Deploy e operação

Serviços mínimos:
- Laravel com PHP-FPM.
- Nginx com HTTPS.
- PostgreSQL protegido em rede privada.
- Worker de filas se houver jobs.
- Storage privado para arquivos.
- Backup externo automatizado.

Checklist:
- [ ] Ambientes separados para desenvolvimento, homologação e produção.
- [ ] HTTPS e cookies seguros.
- [ ] CORS restrito, caso haja frontend externo.
- [ ] Credenciais fora do Git.
- [ ] Migrations executadas por processo controlado.
- [ ] Backup automático e teste de restauração.
- [ ] Logs e alertas de erro.
- [ ] Monitoramento de disponibilidade e disco.
- [ ] Política de retenção de documentos e dados pessoais.
- [ ] Procedimento de rollback.

## 12. Riscos e mitigação

| Risco | Impacto | Mitigação |
|---|---|---|
| Modelo incompatível com regras brasileiras | Crítico | Parecer jurídico antes de habilitar sorteios/pagamentos |
| Autorização externa demorada | Crítico | Separar entrega técnica da autorização regulatória |
| Atraso na integração de assinatura | Médio | Ativação manual controlada no piloto |
| Vazamento entre tenants | Crítico | Policies, testes cruzados e restrições no banco |
| Escopo excessivo | Alto | Congelar funcionalidades não essenciais |
| Falha de webhook | Alto | Idempotência, logs e reconciliação |
| Indisponibilidade | Médio | Backup, monitoramento e recuperação testada |

## 13. Definição de pronto do MVP

- [ ] Repositório e ambiente Docker documentados.
- [ ] Autenticação e recuperação de senha.
- [ ] Multi-tenancy com testes de isolamento.
- [ ] Painel do organizador em Bootstrap.
- [ ] Cadastro e gestão de campanhas.
- [ ] Páginas públicas com SEO básico.
- [ ] Painel administrativo.
- [ ] Planos e controle de assinaturas.
- [ ] Cobrança SaaS integrada ou fluxo manual controlado no piloto.
- [ ] Fluxo de análise e bloqueio jurídico.
- [ ] Testes automatizados dos principais cenários.
- [ ] Deploy, backup e documentação operacional.

## 14. Recomendação final

Para cumprir quatro semanas com um desenvolvedor, use **Laravel + Blade + Bootstrap + PostgreSQL** como monólito modular. Essa escolha evita manter frontend e backend separados, reduz a quantidade de código de integração e permite concentrar o esforço em regras de negócio, segurança e entrega.

O MVP deve validar a operação multiempresa e a receita recorrente da plataforma. A habilitação de sorteios, venda de números, pagamentos de participantes e distribuição de prêmios deve continuar bloqueada até que a modalidade concreta tenha sido juridicamente validada e as autorizações aplicáveis estejam confirmadas.
