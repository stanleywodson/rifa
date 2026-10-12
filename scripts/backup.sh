#!/usr/bin/env bash
# Backup do banco e dos arquivos enviados (storage). Agende no cron do servidor, ex.:
#   0 3 * * * /opt/rifa/scripts/backup.sh >> /var/log/rifa-backup.log 2>&1
# Copie BACKUP_DIR para fora do servidor (S3, B2, rclone...) — backup local não basta.
set -euo pipefail

cd "$(dirname "$0")/.."

ENV_FILE=.env.production
COMPOSE=(docker compose -f docker-compose.prod.yml --env-file "$ENV_FILE")
BACKUP_DIR=${BACKUP_DIR:-/var/backups/rifa}
KEEP_DAYS=${KEEP_DAYS:-14}
STAMP=$(date +%Y%m%d-%H%M%S)

mkdir -p "$BACKUP_DIR"

echo "[$STAMP] Dump do MySQL"
"${COMPOSE[@]}" exec -T mysql sh -c \
    'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"' \
    | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

echo "[$STAMP] Arquivos (storage/app)"
"${COMPOSE[@]}" exec -T app tar -czf - -C /var/www/html/storage app > "$BACKUP_DIR/storage-$STAMP.tar.gz"

find "$BACKUP_DIR" -type f -mtime +"$KEEP_DAYS" -delete

echo "[$STAMP] Concluído em $BACKUP_DIR"
