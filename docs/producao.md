# Ambiente de produção

Stack em Docker Compose (`docker-compose.prod.yml`) para um servidor Linux (VPS) com Docker:

| Serviço | Função |
|---|---|
| `app` | nginx + php-fpm 8.5 (OPcache/JIT), caches do Laravel gerados no start; publicado em `127.0.0.1:${APP_PORT}` |
| `queue` | `queue:work redis` (e-mails, webhooks, geração de números, estornos) |
| `scheduler` | `schedule:work` (reservas vencidas, encerramento de vendas, conciliação, tokens MP) |
| `mysql` | MySQL 8.4 (volume `mysql-data`) |
| `redis` | Redis 7 com senha e AOF (volume `redis-data`) — cache, fila e locks |

A imagem (`docker/production/Dockerfile`) é multi-stage: dependências Composer sem dev, build do Vite com
pnpm e imagem final Alpine. `app`, `queue` e `scheduler` usam a mesma imagem (`CONTAINER_ROLE`). Uploads, PDFs
das atas e logs ficam no volume `app-storage`.

O HTTPS fica a cargo do proxy reverso já existente no servidor: a stack só publica a porta `APP_PORT`
(padrão `8000`) em `127.0.0.1`, sem acesso externo direto.

## Primeira instalação

Requisitos: servidor com Docker + Compose plugin, proxy reverso com TLS (nginx, Caddy, Traefik...) e o DNS do
domínio apontando para o servidor. Recomendado: 2 vCPU / 4 GB RAM.

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

### Proxy reverso

Encaminhe o domínio para `http://127.0.0.1:8000` (ou a `APP_PORT` escolhida) repassando `Host` e os
cabeçalhos `X-Forwarded-*` — sem `X-Forwarded-Proto: https` o Laravel gera links `http://` e o cookie
`secure` não funciona. Aceite uploads de até 25 MB. Exemplo com nginx:

```nginx
server {
    listen 443 ssl http2;
    server_name rifa.exemplo.com.br;
    # ssl_certificate ... (certbot)

    client_max_body_size 25M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
    }
}
```

Com Caddy: `rifa.exemplo.com.br { reverse_proxy 127.0.0.1:8000 }`.

`TRUSTED_PROXIES=172.28.0.0/16` cobre o proxy rodando no próprio host (ele chega ao container pelo gateway da
rede do compose). Se o proxy for outro container, conecte-o à rede `internal` ou ajuste a faixa.

### Administrador

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
- [ ] Firewall liberando apenas 22, 80 e 443 (a `APP_PORT` fica só no localhost)
- [ ] Validação jurídica concluída → `RAFFLE_SALES_ENABLED=true`
