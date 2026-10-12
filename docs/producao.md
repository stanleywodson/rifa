# Ambiente de produção

Stack em Docker Compose (`docker-compose.prod.yml`) para um servidor Linux (VPS) com Docker:

| Serviço | Função |
|---|---|
| `caddy` | Proxy reverso com HTTPS automático (Let's Encrypt), HTTP/3, redireciona `www` |
| `app` | nginx + php-fpm 8.5 (OPcache/JIT), caches do Laravel gerados no start |
| `queue` | `queue:work redis` (e-mails, webhooks, geração de números, estornos) |
| `scheduler` | `schedule:work` (reservas vencidas, encerramento de vendas, conciliação, tokens MP) |
| `mysql` | MySQL 8.4 (volume `mysql-data`) |
| `redis` | Redis 7 com senha e AOF (volume `redis-data`) — cache, fila e locks |

A imagem (`docker/production/Dockerfile`) é multi-stage: dependências Composer sem dev, build do Vite com
pnpm e imagem final Alpine. `app`, `queue` e `scheduler` usam a mesma imagem (`CONTAINER_ROLE`). Uploads, PDFs
das atas e logs ficam no volume `app-storage`. Só as portas 80/443 são expostas.

## Primeira instalação

Requisitos: servidor com Docker + Compose plugin, DNS do domínio (e `www`) apontando para o IP, portas 80/443
abertas. Recomendado: 2 vCPU / 4 GB RAM.

```bash
git clone <repo> /opt/rifa && cd /opt/rifa
cp .env.production.example .env.production
chmod 600 .env.production
```

Preencha `.env.production` (domínio, senhas fortes, SMTP, gateway). Para gerar o `APP_KEY`:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.production build app
docker compose -f docker-compose.prod.yml --env-file .env.production run --rm --no-deps app php artisan key:generate --show
```

Suba tudo e crie os dados iniciais (somente os planos — o `DemoSeeder` só roda em `local`):

```bash
scripts/deploy.sh --no-pull
docker compose -f docker-compose.prod.yml --env-file .env.production run --rm app php artisan db:seed --force
```

Crie o primeiro administrador pelo cadastro e promova-o:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.production exec app \
  php artisan tinker --execute="App\Models\User::where('email', 'voce@dominio.com')->update(['is_platform_admin' => true]);"
```

Com `REQUIRE_ADMIN_2FA=true` o painel admin exige 2FA.

## Deploy de novas versões

```bash
cd /opt/rifa && scripts/deploy.sh
```

O script faz `git pull`, gera a imagem, roda `migrate --force`, recria `app`/`queue`/`scheduler` e espera o
health check (`/up`). Rollback: `git checkout <commit-anterior> && scripts/deploy.sh --no-pull`
(migrations destrutivas precisam de rollback manual).

## Backups

`scripts/backup.sh` gera dump do MySQL e um tar dos arquivos enviados em `/var/backups/rifa`, mantendo 14 dias.
Agende no cron e **copie para fora do servidor** (S3, B2, rclone):

```cron
0 3 * * * /opt/rifa/scripts/backup.sh >> /var/log/rifa-backup.log 2>&1
```

Restauração do banco:

```bash
gunzip -c db-AAAAMMDD-HHMMSS.sql.gz | docker compose -f docker-compose.prod.yml --env-file .env.production \
  exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

## Operação

```bash
alias dc='docker compose -f docker-compose.prod.yml --env-file .env.production'
dc ps                                  # estado e health checks
dc logs -f app queue scheduler         # logs (também em storage/logs/laravel-*.log)
dc exec app php artisan audit:verify   # integridade da trilha de auditoria
dc exec app php artisan queue:failed   # jobs com falha
dc exec app php artisan down           # manutenção (down/up)
```

Após alterar `.env.production`, recrie os containers (`dc up -d app queue scheduler`) — o cache de configuração
é regenerado no start.

## Checklist antes de abrir vendas

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` com `https://`
- [ ] Senhas de banco/Redis fortes e `.env.production` com `chmod 600`
- [ ] SMTP real configurado e testado (SPF/DKIM/DMARC no domínio)
- [ ] Mercado Pago: credenciais de **produção**, Redirect URL `https://<domínio>/painel/configuracoes/mercadopago/retorno`,
      webhook `https://<domínio>/webhooks/payments/mercadopago` com a assinatura em `MERCADOPAGO_WEBHOOK_SECRET`
- [ ] Asaas (se usado): `ASAAS_ENV=production` e webhook `https://<domínio>/webhooks/payments/asaas`
- [ ] Backups agendados e restauração testada
- [ ] Firewall liberando apenas 22, 80 e 443
- [ ] Validação jurídica concluída → `RAFFLE_SALES_ENABLED=true`
