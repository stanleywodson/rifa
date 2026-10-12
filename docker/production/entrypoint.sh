#!/bin/sh
set -e

cd /var/www/html

# O volume de storage começa vazio na primeira subida: recria a estrutura esperada.
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

# Comando avulso (ex.: docker compose run --rm app php artisan migrate --force)
if [ $# -gt 0 ]; then
    exec su-exec www-data "$@"
fi

# Caches gerados com as variáveis de ambiente do container (config, rotas, views, eventos).
su-exec www-data php artisan optimize

case "$CONTAINER_ROLE" in
    app)
        exec supervisord -c /etc/supervisord.conf
        ;;
    queue)
        exec su-exec www-data php artisan queue:work redis \
            --tries=3 --backoff=10 --sleep=3 --max-time=3600 --memory=256
        ;;
    scheduler)
        exec su-exec www-data php artisan schedule:work
        ;;
    *)
        echo "CONTAINER_ROLE inválido: $CONTAINER_ROLE (use app, queue ou scheduler)" >&2
        exit 1
        ;;
esac
