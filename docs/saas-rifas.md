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
  Payments/       PaymentGateway (contrato), AsaasGateway, FakeGateway, confirmação, estorno, webhooks, conciliação
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
