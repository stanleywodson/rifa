# SaaS de Rifas — MVP Fase 1

Implementação do MVP descrito em `prompts/plano-saas-rifa.md` sobre o template **Vuexy** (Laravel 13 + Bootstrap 5).
Todas as telas reutilizam layouts, cards, tabelas (DataTables), formulários e wizards do template; as páginas
originais continuam disponíveis em `/demo/...` (somente `APP_ENV=local`) como referência visual.

## Subir o ambiente

```bash
./vendor/bin/sail up -d                 # app, mysql, redis, mailpit, queue, scheduler
./vendor/bin/sail artisan migrate --seed
npm run build                           # ou: npm run dev
```

| Serviço | URL |
|---|---|
| Aplicação | http://localhost |
| Mailpit (e-mails) | http://localhost:8025 |

Usuários do seed (senha `password`):

| E-mail | Papel |
|---|---|
| admin@rifa.local | Administrador da plataforma |
| organizador@rifa.local | Proprietário da *Paróquia São José* (verificada, rifas em todos os estados) |
| operador@rifa.local | Operador (somente leitura/relatórios) |
| ong@rifa.local | Organizador ainda não verificado (KYC pendente) |

Páginas públicas: `/paroquia-sao-jose`, `/paroquia-sao-jose/moto-0km`, `/paroquia-sao-jose/cesta-de-natal/sorteio`.
Com `PAYMENT_GATEWAY=fake` e ambiente local, a página do pedido mostra o botão **Simular pagamento**.

## Organização do código

```
app/Domain/
  Tenants/        Tenant, Plan, KycDocument, TenantContext, BelongsToTenant (escopo global por tenant_id)
  Raffles/        Raffle, RafflePrize, RaffleMedia, RaffleStateMachine, RaffleReviewService, jobs
  Tickets/        Ticket, TicketReservationService (reserva atômica, aleatórios, grade)
  Orders/         Customer, Order, OrderItem, CheckoutService, ReleaseExpiredReservationsJob
  Payments/       PaymentGateway (contrato), AsaasGateway, MercadoPagoGateway, FakeGateway, confirmação, estorno, webhooks, conciliação
  Draws/          Draw, DrawService (Federal, commit-reveal, manual), LoteriaFederalClient, ata em PDF
  Compliance/     AuditLog (somente inserção, hash encadeado), AuditLogger
  Notifications/  Mailables em fila + notifications_log
app/Http/Controllers/{PublicSite,Organizer,Admin,Webhooks,Account}
resources/views/content/{public,organizer,admin,auth,account}
resources/assets/js/saas-*.js
```

## Regras importantes

- **Isolamento**: o tenant é resolvido no servidor (sessão no painel, slug nas páginas públicas) antes do route
  model binding; models de domínio usam o escopo `BelongsToTenant` e as policies conferem o tenant novamente.
- **Estados da rifa**: `draft → pending_review → published → sales_closed → drawn → completed` (+ `changes_requested`,
  `rejected`, `cancelled`). Somente `RaffleStateMachine` altera o status. Após a publicação, preço, quantidade,
  método de sorteio, regra de número não vendido e regulamento ficam congelados (exceção no model).
- **Aprovação**: exige KYC do organizador aprovado; gera a semente secreta e publica `commit_hash = sha256(semente)`;
  os números são criados em lote e a rifa é publicada ao final.
- **Reserva**: `UPDATE ... WHERE status = 'free'` dentro de transação — se menos linhas forem afetadas, nada é
  reservado. Aleatórios usam lock Redis + `FOR UPDATE SKIP LOCKED`. TTL em `RAFFLE_RESERVATION_TTL`.
- **Pagamento**: webhook validado e gravado de forma idempotente (`webhook_events` com UNIQUE), processado em fila;
  conciliação a cada 5 min. Pagamento após expiração é confirmado se os números ainda estiverem livres; caso
  contrário é estornado automaticamente.
- **Sorteio**: a prova pública traz todos os dados para recalcular o resultado; registro imutável com hash encadeado
  e ata em PDF. `php artisan audit:verify` valida a trilha de auditoria.

## Asaas

1. Crie uma conta sandbox e gere a chave de API (conta da plataforma).
2. `.env`: `PAYMENT_GATEWAY=asaas`, `ASAAS_ENV=sandbox`, `ASAAS_API_KEY=...`, `ASAAS_WEBHOOK_TOKEN=<token escolhido>`.
3. Configure o webhook de cobranças no Asaas apontando para `https://<seu-dominio>/webhooks/payments/asaas`,
   com o mesmo token de autenticação (header `asaas-access-token`). Em desenvolvimento use `sail share` ou ngrok.
4. Cada organizador cria a subconta em **Configurações → Recebimentos**; as cobranças saem com split
   (`100 − taxa`% para a carteira do organizador).

## Mercado Pago (split marketplace)

O organizador **conecta a própria conta** Mercado Pago via OAuth; a cobrança Pix é criada com o token dele e a taxa
da plataforma vai como `application_fee` — o restante cai direto na conta do organizador.

1. Em [Suas integrações](https://www.mercadopago.com.br/developers/panel/app) crie uma aplicação (pagamentos online,
   marketplace/split) e cadastre a **Redirect URL**: `https://<seu-dominio>/painel/configuracoes/mercadopago/retorno`.
2. Em **Webhooks** da aplicação, aponte para `https://<seu-dominio>/webhooks/payments/mercadopago`, evento
   *Pagamentos*, e copie a **assinatura secreta**.
3. `.env`: `PAYMENT_GATEWAYS=mercadopago` (ou `asaas,mercadopago` junto com `PAYMENT_GATEWAY=asaas`),
   `MERCADOPAGO_ENV=sandbox`, `MERCADOPAGO_CLIENT_ID`, `MERCADOPAGO_CLIENT_SECRET`, `MERCADOPAGO_WEBHOOK_SECRET`.
   Depois de alterar o `.env`, reinicie a fila: `sail artisan queue:restart`.
4. Em **Configurações → Recebimentos** o organizador escolhe o gateway; no Mercado Pago clica em *Conectar*. A conta
   precisa ter chave Pix. Para testar, use [usuários de teste](https://www.mercadopago.com.br/developers/pt/docs/your-integrations/test/accounts)
   (vendedor conecta, comprador paga). No marketplace, **a aplicação também precisa ser criada por um usuário de
   teste** (o "marketplace"); aplicação de conta real + vendedor de teste resulta em `user_allowed_only_in_test`.
   Contas de teste usam as credenciais de produção; `MERCADOPAGO_TEST_TOKEN=true` só para testar com conta real.
   O pagador precisa ser o comprador de teste: defina `MERCADOPAGO_TEST_PAYER_EMAIL` (ignorado em produção);
   e-mail real resulta em `Unauthorized use of live credentials`.

Detalhes:

- O webhook só avisa a mudança: o status é sempre **consultado na API** com o token do organizador, então um aviso
  forjado não confirma pagamento. Com `MERCADOPAGO_WEBHOOK_SECRET` a assinatura `x-signature` é obrigatória
  (sem o segredo, os avisos só são aceitos fora de produção).
- O Pix do Mercado Pago tem validade mínima de 30 min; a reserva continua com `RAFFLE_RESERVATION_TTL` e a cobrança é
  cancelada quando a reserva expira.
- O e-mail do comprador é obrigatório no checkout de organizadores que recebem pelo Mercado Pago.
- Tokens OAuth valem 180 dias: são renovados antes de cobrar e diariamente (`RefreshMercadoPagoTokensJob`).
- Trocar/desconectar a conta só é permitido sem rifas em andamento nem Pix pendentes.

> Vendas reais dependem do parecer jurídico (seção 2 do plano). `RAFFLE_SALES_ENABLED=false` mantém as páginas
> públicas, mas bloqueia reservas e cobranças.

## Testes

```bash
./vendor/bin/sail artisan test
```

Cobrem: isolamento entre tenants, papéis, fluxo de aprovação, congelamento das regras, limites de plano,
checkout, corrida real entre 8 processos pelo mesmo número (`pcntl_fork`), expiração, webhook duplicado,
pagamento tardio com estorno, sorteios (Federal, commit-reveal, regras de não vendido), adulteração da auditoria
e renderização de todas as telas.

## Fora do MVP (próximas fases)

Cartão, afiliados, cupons/descontos progressivos, WhatsApp, domínio próprio/white-label, cobrança recorrente da
assinatura (planos são atribuídos pelo admin), API pública, Reverb (a grade usa polling a cada 5 s), Turnstile.
