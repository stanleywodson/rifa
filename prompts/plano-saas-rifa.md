# Plano de Implementação — SaaS de Rifas Online

> Plataforma multi-tenant onde organizadores criam rifas, vendem números via Pix, e o prêmio é sorteado para o número contemplado de forma transparente e auditável.

---

## 1. Visão Geral

### 1.1 Objetivo
Permitir que organizadores (pessoas físicas autorizadas, associações, ONGs, igrejas, empresas) criem e gerenciem rifas online, vendam números, recebam pagamentos automaticamente e realizem o sorteio de forma auditável, com página pública própria e painel de gestão.

### 1.2 Proposta de valor
- **Organizador:** criar rifa em minutos, receber via Pix automaticamente, relatórios e prestação de contas prontas.
- **Comprador:** escolher números, pagar por Pix, receber comprovante e acompanhar o sorteio.
- **Plataforma:** receita recorrente (assinatura) + taxa por transação.

### 1.3 Escopo
| Dentro do escopo | Fora do escopo (inicial) |
|---|---|
| Criação/gestão de rifas | App nativo (iOS/Android) |
| Venda de números com reserva | Marketplace de prêmios |
| Pagamento via Pix (e cartão na V1) | Apostas esportivas / jogos de azar |
| Sorteio auditável | Gateway de pagamento próprio |
| Painel do organizador e admin | Integração com redes de loteria estrangeiras |
| Notificações (e-mail/WhatsApp) | |

---

## 2. Conformidade Legal (Brasil) — ETAPA CRÍTICA

> ⚠️ **Este item deve ser validado com advogado especializado antes de qualquer lançamento.** Este plano não é aconselhamento jurídico.

Pontos de atenção:

1. **Rifas e sorteios são regulados.** No Brasil, sorteios com distribuição de prêmios dependem de autorização prévia do órgão competente (Secretaria de Prêmios e Apostas — SPA/MF, antiga SECAP), conforme **Lei 5.768/1971** e **Decreto 70.951/1972**. Rifa sem autorização pode configurar contravenção penal (**Decreto-Lei 3.688/1941, art. 50**).
2. **Rifas beneficentes** (associações/ONGs) têm regras próprias e também exigem autorização/regularidade.
3. **Prêmios:** a regulamentação costuma exigir que o prêmio exista e esteja disponível, e define regras de prestação de contas e prazo.
4. **Sorteio:** o formato mais aceito é basear o resultado na **Loteria Federal** (fonte externa, pública e verificável).
5. **LGPD (Lei 13.709/2018):** tratamento de dados de compradores e organizadores.
6. **Plataforma como intermediária:** definir juridicamente o papel (software/SaaS vs. promotora do sorteio), Termos de Uso, responsabilidade do organizador.
7. **Pagamentos:** uso de IP/instituição de pagamento regulada (split/subconta) para não custodiar dinheiro de terceiros indevidamente.

### Ações de compliance no produto
- [ ] Campo obrigatório: **número/certificado de autorização** da rifa (upload do documento).
- [ ] Fluxo de **aprovação manual** da rifa pelo time admin antes de publicar.
- [ ] KYC do organizador (CPF/CNPJ, documento, selfie opcional).
- [ ] Termos de Uso + Política de Privacidade + Regulamento da rifa gerado/anexado.
- [ ] Sorteio baseado em **resultado externo verificável** (Loteria Federal) ou algoritmo auditável (ver seção 8).
- [ ] Trilha de auditoria imutável de todas as ações críticas.
- [ ] Canal de denúncia e moderação.
- [ ] Bloqueio de menores de 18 anos.

---

## 3. Personas e Papéis

| Papel | Descrição | Permissões principais |
|---|---|---|
| **Visitante/Comprador** | Quem compra números | Ver rifa, reservar/comprar, ver seus números |
| **Organizador (Tenant Owner)** | Dono da conta/rifas | CRUD rifas, ver vendas, executar sorteio, saque |
| **Equipe do organizador** | Operadores/vendedores | Vendas manuais, relatórios (permissões limitadas) |
| **Afiliado/Vendedor** (V1) | Divulga com link próprio | Ver comissões |
| **Admin da plataforma** | Time interno | Aprovar rifas, moderar, financeiro, suporte |
| **Auditor** (opcional) | Terceiro verificador | Leitura da trilha de auditoria do sorteio |

---

## 4. Modelo de Negócio

### 4.1 Fontes de receita
1. **Assinatura mensal** por plano (limites de rifas/números).
2. **Taxa por transação** (% sobre vendas aprovadas) — ex.: 2% a 8%.
3. **Recursos premium:** domínio próprio, white-label, afiliados, relatórios avançados.

### 4.2 Planos sugeridos
| Plano | Preço | Rifas ativas | Taxa | Extras |
|---|---|---|---|---|
| Starter | Grátis/baixo | 1 | Maior | Subdomínio da plataforma |
| Pro | Médio | 10 | Média | Cores/logo, afiliados, relatórios |
| Business | Alto | Ilimitado | Menor | Domínio próprio, API, white-label |

### 4.3 Fluxo financeiro (recomendado)
Usar **gateway com split de pagamento / subcontas** (ex.: Asaas, Pagar.me, Mercado Pago, Efí, Stripe Connect equivalente) para que o valor vá direto ao organizador e a taxa da plataforma seja separada automaticamente — **a plataforma não deve segurar o dinheiro** do prêmio/organizador.

---

## 5. Funcionalidades

### 5.1 MVP (Fase 1)
**Organizador**
- Cadastro, login, verificação de e-mail, KYC básico
- Criar rifa: título, descrição, imagens do prêmio, quantidade de números (ex.: 100, 1.000, 10.000), preço por número, data do sorteio, regulamento, nº de autorização
- Publicar rifa (após aprovação)
- Dashboard: números vendidos, receita, conversão
- Lista de compradores e números
- Executar/registrar sorteio
- Exportar relatório (CSV/PDF)

**Comprador**
- Página pública da rifa (SEO-friendly, compartilhável)
- Grade de números com status (livre / reservado / pago)
- Seleção manual ou "números aleatórios"
- Checkout com dados mínimos (nome, WhatsApp, e-mail, CPF se exigido)
- Pagamento Pix (QR Code + copia e cola) com expiração
- Confirmação automática (webhook) + comprovante por e-mail/WhatsApp
- "Meus números" (consulta por CPF/telefone + código)

**Admin**
- Aprovar/reprovar rifas
- Gerenciar usuários e organizadores
- Visão financeira e de transações
- Log de auditoria

### 5.2 V1 (Fase 2)
- Cartão de crédito
- Cotas com **desconto progressivo** (compre 10, ganhe 2)
- **Afiliados/vendedores** com comissão e link rastreável
- Notificações WhatsApp (API oficial ou provedor)
- Domínio personalizado + white-label
- Cupons de desconto
- Múltiplos prêmios (1º, 2º, 3º lugar)
- Transmissão ao vivo do sorteio (embed)
- Integração Pixel/Analytics (Meta, Google)

### 5.3 V2 (Fase 3)
- API pública + webhooks para organizadores
- Rifas por "números premiados instantâneos" (se juridicamente permitido)
- App PWA com push
- Programa de reputação/selo de organizador verificado
- Painel de prestação de contas público
- Multi-idioma / multi-moeda

---

## 6. Fluxos Principais

### 6.1 Criação e publicação da rifa
```
Organizador cria rifa (rascunho)
   → anexa autorização/regulamento
   → envia para análise
   → Admin aprova / solicita ajustes / reprova
   → Rifa PUBLICADA (página pública ativa)
```

### 6.2 Compra de números
```
Comprador escolhe números
   → sistema RESERVA (TTL 10–15 min) com lock transacional
   → gera cobrança Pix no gateway
   → comprador paga
   → webhook confirma → números viram PAGOS
   → comprovante enviado
   (se expirar → números voltam a LIVRE)
```

### 6.3 Sorteio
```
Rifa atinge data/meta (ou todos vendidos)
   → status "AGUARDANDO SORTEIO" (vendas encerradas)
   → define fonte do resultado (Loteria Federal ou RNG auditável)
   → sistema calcula número contemplado
   → registra prova (hash, fonte, timestamp, evidências)
   → notifica ganhador e organizador
   → publica resultado na página
   → organizador entrega prêmio e anexa comprovante de entrega
```

### 6.4 Encerramento e prestação de contas
- Comprovante de entrega do prêmio (foto/vídeo/recibo)
- Relatório final (vendas, taxas, repasses)
- Rifa arquivada, dados retidos conforme política

---

## 7. Arquitetura Técnica

### 7.1 Stack sugerida
> Alinhada a stack Laravel + Next.js + Docker + Nginx.

| Camada | Tecnologia |
|---|---|
| **Backend** | Laravel 11+ (PHP 8.3), API REST, Sanctum/Passport |
| **Frontend público + painel** | Blade + Tailwind CSS + bootstrap |
| **Banco de dados** | PostgreSQL (preferível) ou MySQL 8 |
| **Cache / Locks / Filas** | Redis (cache, locks distribuídos, filas) |
| **Filas/Jobs** | Laravel Queues + Horizon |
| **Tempo real** | Laravel Reverb / Pusher / SSE (atualizar grade de números) |
| **Armazenamento** | S3-compatível (Cloudflare R2 / AWS S3 / MinIO) |
| **Pagamentos** | Gateway com Pix + split (a escolher) |
| **E-mail** | Amazon SES / Resend / Postmark |
| **WhatsApp** | API oficial (Meta Cloud) ou provedor (Z-API, Twilio) |
| **Observabilidade** | Sentry, Grafana/Loki/Prometheus, Laravel Telescope (dev) |
| **Infra** | Docker, Docker Compose / Swarm / Kubernetes, Nginx, Cloudflare |
| **CI/CD** | GitHub Actions |

### 7.2 Diagrama lógico
```
[Cloudflare CDN/WAF]
        │
     [Nginx]
     ┌──┴─────────────┐
[Next.js SSR]    [Laravel API]──┬── [PostgreSQL]
                       │        ├── [Redis] (cache/locks/filas)
                       │        └── [S3] (arquivos)
                 [Queue Workers / Horizon]
                       │
          ┌────────────┼─────────────┐
   [Gateway Pix]  [E-mail/WhatsApp]  [Loteria Federal API/scraper]
          │
      [Webhooks] ──► Laravel API
```

### 7.3 Multi-tenancy
- **Modelo:** banco único, coluna `tenant_id` em todas as tabelas de domínio (mais simples para começar), com Global Scopes no Laravel.
- Opcional futuro: schema por tenant para grandes clientes.
- Subdomínio por organizador: `organizador.suaplataforma.com.br`; domínio próprio via CNAME + SSL automático (Caddy/Traefik ou Cloudflare for SaaS).

### 7.4 Módulos do backend (organização sugerida)
```
app/Domain/
  Tenants/         # contas, planos, assinaturas
  Users/           # auth, papéis, KYC
  Raffles/         # rifas, prêmios, regras
  Tickets/         # números, reservas, status
  Orders/          # pedidos, itens
  Payments/        # integração gateway, webhooks, split
  Draws/           # sorteio, provas, auditoria
  Affiliates/      # comissões
  Notifications/   # e-mail, WhatsApp
  Compliance/      # aprovação, documentos, logs
  Reports/         # relatórios/exportações
```

---

## 8. Sorteio — Regras, Justiça e Auditabilidade

Este é o **coração da confiança** do produto.

### 8.1 Métodos suportados
1. **Loteria Federal (recomendado):** o número contemplado é derivado de dados públicos do concurso (ex.: últimos dígitos do 1º prêmio, ou combinação de dezenas). Regra definida no regulamento **antes** do início das vendas.
2. **RNG auditável (commit-reveal):**
   - Antes da venda: plataforma publica o **hash** de uma semente secreta (`commit`).
   - Após encerrar: revela a semente + combina com dado público externo (ex.: resultado Loteria Federal ou hash de bloco).
   - Qualquer pessoa pode verificar `hash(semente) == commit` e recalcular o vencedor.
3. **Sorteio manual ao vivo** (com registro em vídeo e ata no sistema) — para organizadores que exigem.

### 8.2 Algoritmo de mapeamento (exemplo)
```
resultado_base = dezenas/dígitos da Loteria Federal (string numérica)
numero_sorteado = (inteiro(resultado_base) mod total_numeros) + offset_inicial
se numero_sorteado não foi vendido → aplicar regra definida no regulamento
   (ex.: próximo número pago acima, ou novo sorteio, ou rifa anulada)
```

### 8.3 Prova de sorteio (registro imutável)
Guardar em tabela `draws` e `audit_logs`:
- método, fonte, concurso/URL, dados brutos usados
- semente + hash de commit (se aplicável)
- número contemplado, ganhador, timestamp, IP/usuário executor
- hash encadeado do registro (hash chain) para detectar adulteração
- PDF de **ata do sorteio** gerado automaticamente

### 8.4 Regras de integridade
- Após a rifa estar PUBLICADA, **regras do sorteio não podem ser alteradas**.
- Vendas **encerradas automaticamente** antes do sorteio.
- Nenhum admin pode alterar números pagos sem log e justificativa.
- Número ganhador deve estar **PAGO** e ter pedido confirmado.

---

## 9. Modelagem de Dados (principais entidades)

```
tenants (id, nome, slug, plano_id, status, taxa_percentual, ...)
users (id, tenant_id, nome, email, cpf, telefone, senha, role, kyc_status, ...)
plans (id, nome, preco, limites_json, ...)
subscriptions (id, tenant_id, plan_id, status, ciclo, ...)

raffles (id, tenant_id, titulo, slug, descricao, status, total_numeros,
         preco_numero, data_sorteio, metodo_sorteio, regras_json,
         numero_autorizacao, documento_autorizacao_url,
         commit_hash, aprovado_por, aprovado_em, ...)
raffle_prizes (id, raffle_id, posicao, titulo, descricao, imagem_url, valor_estimado)
raffle_media (id, raffle_id, tipo, url)
raffle_discount_rules (id, raffle_id, qtd_minima, desconto/bonus)

tickets (id, raffle_id, numero, status[LIVRE|RESERVADO|PAGO|CANCELADO],
         order_id, reservado_ate, ...)
          -- UNIQUE(raffle_id, numero)

customers (id, tenant_id, nome, cpf, email, telefone, ...)
orders (id, tenant_id, raffle_id, customer_id, affiliate_id, status,
        subtotal, desconto, total, expira_em, ...)
order_items (id, order_id, ticket_id, preco)

payments (id, order_id, gateway, gateway_id, metodo, status, valor,
          pix_qr, pix_copia_cola, pago_em, payload_json, ...)
payment_splits (id, payment_id, destinatario, valor, tipo[PLATAFORMA|ORGANIZADOR|AFILIADO])
payouts (id, tenant_id, valor, status, gateway_ref, ...)
refunds (id, payment_id, motivo, valor, status, ...)

draws (id, raffle_id, metodo, fonte_ref, dados_brutos_json, seed_revelada,
       numero_sorteado, ticket_id, customer_id, executado_por, executado_em,
       hash_registro, hash_anterior, ata_pdf_url)

affiliates (id, tenant_id, user_id, codigo, comissao_percentual, ...)
commissions (id, affiliate_id, order_id, valor, status)

notifications_log (id, canal, destinatario, template, status, payload_json, ...)
audit_logs (id, tenant_id, user_id, acao, entidade, entidade_id,
            antes_json, depois_json, ip, user_agent, hash, created_at)
webhook_events (id, origem, evento_id UNIQUE, payload_json, processado_em)
```

**Índices importantes:** `tickets(raffle_id, status)`, `tickets(reservado_ate)`, `orders(status, expira_em)`, `payments(gateway_id)`, `webhook_events(evento_id)`.

---

## 10. Reserva de Números e Concorrência

Problema clássico: dois compradores escolhendo o mesmo número ao mesmo tempo.

### Estratégia
1. **Constraint única** `(raffle_id, numero)` + estado do ticket.
2. Reserva com **transação + `SELECT ... FOR UPDATE`** (ou `UPDATE ... WHERE status='LIVRE'` atômico verificando linhas afetadas).
3. **TTL de reserva** (ex.: 10 min) com `reservado_ate`.
4. **Job agendado** (a cada minuto) libera reservas expiradas; complementar com expiração via Redis (keyspace events / delayed jobs).
5. **Lock distribuído** (Redis) por rifa em operações de "números aleatórios" para evitar colisão.
6. Atualização em tempo real da grade via WebSocket/SSE.

### Para rifas grandes (10k–1M números)
- Não criar uma linha por número no momento da criação se for gigantesco; considerar:
  - Gerar linhas em lote (chunked jobs) para até ~100k números.
  - Acima disso, modelo **esparso** (só registra números reservados/vendidos; livres são implícitos).
- Paginar a grade no frontend e usar virtualização.

---

## 11. Pagamentos

### 11.1 Fluxo Pix
1. Cria `order` (status `AGUARDANDO_PAGAMENTO`) e reserva números.
2. Chama API do gateway → recebe QR Code/copia e cola + `txid`.
3. Exibe com contador regressivo.
4. **Webhook** do gateway → valida assinatura → idempotência (`webhook_events.evento_id`) → marca pagamento `PAGO` → confirma tickets → dispara notificações.
5. **Fallback de conciliação:** job que consulta status no gateway para cobranças pendentes (caso webhook falhe).

### 11.2 Boas práticas
- **Idempotência** em webhooks e criação de cobranças.
- Nunca confiar apenas no front para confirmar pagamento.
- Pagamento tardio (após expirar reserva): se número ainda livre → confirma; senão → **estorno automático** ou crédito.
- Split automático: organizador / plataforma / afiliado.
- Conciliação financeira diária.
- Tratamento de **estornos/chargebacks** e política de reembolso (rifa cancelada → reembolso total).

### 11.3 Saques/Repasses
- Preferencial: repasse automático via subconta/split do gateway.
- Se houver saldo na plataforma: regras de liberação (ex.: D+N após o sorteio ou prova de entrega) para reduzir risco de golpe.

---

## 12. API (visão geral REST)

**Pública**
```
GET    /api/public/raffles/{slug}
GET    /api/public/raffles/{slug}/tickets?status=&page=
POST   /api/public/raffles/{slug}/reserve        # reserva números
POST   /api/public/orders                         # cria pedido + cobrança
GET    /api/public/orders/{code}                  # status do pedido
GET    /api/public/my-tickets?cpf=&code=
GET    /api/public/raffles/{slug}/draw            # prova do sorteio
POST   /api/webhooks/payments/{gateway}           # webhooks
```

**Organizador (autenticada)**
```
POST   /api/raffles
PUT    /api/raffles/{id}
POST   /api/raffles/{id}/submit-review
POST   /api/raffles/{id}/close-sales
POST   /api/raffles/{id}/draw
GET    /api/raffles/{id}/orders
GET    /api/raffles/{id}/export
GET    /api/dashboard/metrics
```

**Admin**
```
GET    /api/admin/raffles?status=pending
POST   /api/admin/raffles/{id}/approve
POST   /api/admin/raffles/{id}/reject
GET    /api/admin/audit-logs
GET    /api/admin/financial
```

Documentar com **OpenAPI/Swagger** (Scribe/Scramble) e versionar (`/v1`).

---

## 13. Segurança, Antifraude e LGPD

### Segurança
- HTTPS obrigatório, HSTS, WAF (Cloudflare), rate limiting por IP/rota
- Proteção de CSRF, XSS, SQL injection (Eloquent/prepared statements), validação rigorosa
- Hash de senha (Argon2/Bcrypt), 2FA para organizadores e admins
- Assinatura/validação HMAC em webhooks, whitelist de IPs quando possível
- Segredos em variáveis de ambiente/secret manager
- Backups criptografados diários + teste de restore
- Princípio do menor privilégio (RBAC com policies)
- Logs sem dados sensíveis (mascarar CPF/cartão)
- Nunca armazenar dados de cartão (usar tokenização do gateway — PCI fora do escopo)

### Antifraude / Abuso
- Análise de organizadores novos (limite de arrecadação inicial)
- Bloqueio de rifas suspeitas, denúncias de usuários
- Detecção de múltiplas compras anômalas, bots (captcha/Turnstile no checkout)
- Retenção de repasse até comprovação de entrega do prêmio (opcional)
- Verificação de documento/selfie para organizadores

### LGPD
- Base legal definida (execução de contrato / obrigação legal)
- Política de privacidade e consentimento para marketing
- Direitos do titular (acesso, correção, exclusão) — rotina e painel
- Minimização de dados e política de retenção
- DPO/encarregado designado
- Registro de operações de tratamento (ROPA)
- Contratos com operadores (gateway, e-mail, hospedagem)

---

## 14. Frontend / UX

### Páginas públicas
- Home da plataforma (landing page, planos, FAQ)
- Página da rifa: galeria do prêmio, barra de progresso, grade de números, regulamento, ganhador, organizador verificado, botões de compartilhar
- Checkout enxuto (mobile-first — maioria dos compradores vem de WhatsApp/Instagram)
- Página "Meus números"
- Página de resultado/prova do sorteio

### Painel do organizador
- Dashboard, rifas, pedidos/compradores, financeiro, afiliados, configurações, marca/domínio

### Painel admin
- Fila de aprovação, usuários, financeiro, denúncias, logs

### Diretrizes
- **Mobile-first**, carregamento rápido (Core Web Vitals), SSR/ISR para SEO e OG tags (preview no WhatsApp)
- Acessibilidade (WCAG AA)
- Design system consistente, tema claro/escuro opcional
- Microcopy que transmita confiança (selos, prova do sorteio, avaliações)

---

## 15. Notificações

| Evento | Canal | Destinatário |
|---|---|---|
| Pedido criado / Pix gerado | E-mail/WhatsApp | Comprador |
| Pagamento confirmado + números | E-mail/WhatsApp | Comprador |
| Reserva prestes a expirar | WhatsApp | Comprador |
| Nova venda | E-mail/Push | Organizador |
| Rifa aprovada/reprovada | E-mail | Organizador |
| Sorteio realizado | E-mail/WhatsApp | Todos os compradores |
| Parabéns, você ganhou! | E-mail/WhatsApp | Ganhador |
| Alerta antifraude | E-mail/Slack | Admin |

Templates versionados, fila dedicada e *retry* com backoff.

---

## 16. Infraestrutura e DevOps

### Ambientes
`local` → `staging` → `production` (paridade via Docker)

### Containers
```
nginx | php-fpm (laravel) | queue-worker (horizon) | scheduler
next (node) | postgres | redis | (minio em dev) | mailpit (dev)
```

### Pipeline CI/CD (GitHub Actions)
1. Lint (Pint/PHPStan/ESLint) + testes (Pest/PHPUnit, Vitest/Playwright)
2. Build das imagens Docker
3. Deploy em staging (automático) → smoke tests
4. Deploy em produção (manual com aprovação) com migrations seguras
5. Rollback documentado

### Observabilidade
- Logs centralizados, métricas de aplicação e filas, alertas (fila travada, webhook falhando, erro 5xx, latência)
- Health checks e uptime monitoring
- Dashboard de negócio (vendas/min, conversão do checkout)

### Escalabilidade
- Picos (rifa viral/sorteio): cache de páginas públicas (CDN/ISR), leitura da grade com cache curto + invalidação por evento, workers escaláveis horizontalmente, banco com réplica de leitura quando necessário
- Testes de carga (k6) antes do lançamento

### Backup/DR
- Backup diário + PITR no banco, retenção definida, RPO/RTO documentados

---

## 17. Estratégia de Testes

| Tipo | Foco |
|---|---|
| Unitários | Regras de sorteio, cálculo de taxas/splits, descontos |
| Integração | Fluxo reserva→pagamento→confirmação, webhooks |
| Concorrência | Dois compradores no mesmo número (testes paralelos) |
| E2E (Playwright) | Jornada completa do comprador e do organizador |
| Carga (k6) | Milhares de reservas simultâneas |
| Segurança | OWASP Top 10, pentest antes do go-live |
| Contrato | Mock/sandbox do gateway |
| Auditoria do sorteio | Reproduzir resultado a partir da prova publicada |

Meta de cobertura: ≥ 80% nas regras de negócio críticas (pagamentos e sorteio).

---

## 18. Roadmap e Cronograma (estimativa)

> Equipe de referência: 2 devs full-stack, 1 designer (parcial), 1 PO/jurídico (consultivo). Ajustar conforme time.

### Fase 0 — Descoberta e Fundação (2–3 semanas)
- Validação jurídica e modelo de operação
- Escolha do gateway (Pix + split) e contrato/sandbox
- Definição de regras do sorteio e regulamento-modelo
- Protótipo UX (Figma) e design system
- Setup de repositórios, Docker, CI/CD, ambientes

### Fase 1 — MVP (8–10 semanas)
| Sprint | Entregas |
|---|---|
| 1–2 | Auth, multi-tenancy, RBAC, cadastro/KYC básico, painel base |
| 3–4 | CRUD de rifas, upload de mídia, aprovação admin, página pública |
| 5–6 | Grade de números, reserva com lock/TTL, pedidos |
| 7–8 | Pix + webhooks + conciliação, comprovantes, "meus números" |
| 9–10 | Sorteio (Loteria Federal + prova/ata), notificações e-mail, relatórios, hardening, testes de carga |

**Go-live fechado (beta)** com 3–5 organizadores confiáveis.

### Fase 2 — V1 (6–8 semanas)
- Planos/assinaturas e cobrança SaaS
- Afiliados e comissões, cupons, descontos progressivos
- WhatsApp, cartão de crédito, múltiplos prêmios
- Domínio próprio/white-label, analytics/pixels
- Painel financeiro e repasses avançados

### Fase 3 — V2 (contínuo)
- API pública, PWA, selo de verificação, prestação de contas pública
- Otimizações de escala, internacionalização, novos métodos de pagamento

---

## 19. KPIs e Métricas

**Produto/Negócio**
- GMV (volume transacionado), receita (MRR + taxas), ticket médio
- Taxa de conversão do checkout (visita → reserva → pago)
- % reservas expiradas, tempo médio até pagamento
- Rifas criadas/aprovadas/concluídas, churn de organizadores
- NPS de organizadores e compradores

**Técnicos**
- Latência p95 de reserva e checkout, taxa de erro, fila de jobs, falhas de webhook
- Disponibilidade (SLO 99,9%)

**Confiança/Risco**
- Taxa de denúncias, chargebacks, rifas reprovadas, tempo de entrega do prêmio

---

## 20. Riscos e Mitigações

| Risco | Impacto | Mitigação |
|---|---|---|
| Irregularidade legal da rifa | Crítico | Aprovação obrigatória com documentos, assessoria jurídica, bloqueio de rifas sem autorização |
| Golpes/organizador fraudulento | Alto | KYC, limites iniciais, retenção de repasse, denúncias, selo verificado |
| Disputa de número / falha de concorrência | Alto | Locks, constraints únicas, testes de concorrência |
| Falha de webhook / pagamento órfão | Alto | Idempotência + conciliação periódica + estorno automático |
| Questionamento da lisura do sorteio | Alto | Loteria Federal, commit-reveal, ata e prova públicas |
| Pico de tráfego | Médio | CDN, cache, workers elásticos, teste de carga |
| Vazamento de dados (LGPD) | Alto | Criptografia, RBAC, auditoria, pentest, plano de resposta a incidentes |
| Dependência de gateway | Médio | Camada de abstração (`PaymentGatewayInterface`) para trocar/adicionar |
| Bloqueio de contas/links em redes sociais | Médio | Domínios próprios, múltiplos canais de aquisição |

---

## 21. Checklist de Lançamento (Go-Live)

**Jurídico**
- [ ] Parecer jurídico sobre modelo de operação
- [ ] Termos de Uso, Privacidade, Regulamento-modelo
- [ ] Fluxo de autorização/aprovação validado

**Produto**
- [ ] Fluxo completo testado em produção com valores reais (piloto)
- [ ] Sorteio reproduzível e ata gerada
- [ ] Reembolso/cancelamento testado

**Técnico**
- [ ] Backups + restore testado
- [ ] Monitoramento e alertas ativos
- [ ] Teste de carga e pentest concluídos
- [ ] Rate limit, WAF e captcha configurados
- [ ] Runbook de incidentes e rollback

**Negócio**
- [ ] Suporte (canal, SLA, FAQ)
- [ ] Onboarding de organizadores-piloto
- [ ] Precificação final e cobrança SaaS

---

## 22. Backlog Inicial (épicos)

1. **EP-01** Fundação: auth, tenants, RBAC, auditoria
2. **EP-02** Gestão de rifas e aprovação
3. **EP-03** Página pública e SEO
4. **EP-04** Grade de números, reserva e concorrência
5. **EP-05** Checkout e Pix (webhook + conciliação)
6. **EP-06** Sorteio auditável e ata
7. **EP-07** Notificações
8. **EP-08** Dashboard e relatórios
9. **EP-09** Painel admin e moderação
10. **EP-10** Assinaturas e planos SaaS
11. **EP-11** Afiliados e cupons
12. **EP-12** Domínio próprio / white-label
13. **EP-13** API pública e webhooks
14. **EP-14** Observabilidade, segurança e escala

---

## 23. Próximos Passos Imediatos

1. Validar o modelo jurídico/operacional com advogado especialista em sorteios/promoções.
2. Escolher o gateway (comparar taxas Pix, split, subcontas, KYC embutido).
3. Fechar regras do sorteio e redigir regulamento-modelo.
4. Prototipar telas críticas (página da rifa + checkout) e testar com usuários reais.
5. Subir o esqueleto do projeto (Laravel + Next.js + Docker + CI) e iniciar o Sprint 1.

---

*Documento vivo — revisar a cada fase. Última atualização: 10/10/2026.*
