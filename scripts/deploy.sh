#!/usr/bin/env bash
# Deploy de produção no servidor: atualiza o código, gera a imagem, roda migrations
# e recria os containers. Uso: scripts/deploy.sh [--no-pull]
set -euo pipefail

cd "$(dirname "$0")/.."

ENV_FILE=.env.production
COMPOSE=(docker compose -f docker-compose.prod.yml --env-file "$ENV_FILE")

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Arquivo $ENV_FILE não encontrado. Copie .env.production.example e preencha." >&2
    exit 1
fi

if [[ "${1:-}" != "--no-pull" ]]; then
    git pull --ff-only
fi

REVISION=$(git rev-parse --short HEAD)
echo "==> Build da imagem ($REVISION)"
"${COMPOSE[@]}" build app

echo "==> Banco e Redis"
"${COMPOSE[@]}" up -d mysql redis

echo "==> Migrations"
"${COMPOSE[@]}" run --rm app php artisan migrate --force

echo "==> Containers da aplicação"
"${COMPOSE[@]}" up -d --remove-orphans app queue scheduler

echo "==> Aguardando health check"
for _ in $(seq 1 30); do
    status=$(docker inspect -f '{{.State.Health.Status}}' "$("${COMPOSE[@]}" ps -q app)" 2>/dev/null || true)
    if [[ "$status" == "healthy" ]]; then
        echo "OK — versão $REVISION no ar."
        docker image prune -f > /dev/null
        exit 0
    fi
    sleep 3
done

echo "A aplicação não ficou saudável. Logs: ${COMPOSE[*]} logs app" >&2
exit 1
