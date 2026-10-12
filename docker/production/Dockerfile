# syntax=docker/dockerfile:1
# Imagem de produção: nginx + php-fpm (supervisord). A mesma imagem roda a fila e o
# scheduler alterando CONTAINER_ROLE (app | queue | scheduler).

ARG PHP_VERSION=8.5
ARG NODE_VERSION=22

# ---------------------------------------------------------------------------
# Base PHP com as extensões usadas pela aplicação
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache nginx supervisor su-exec tzdata curl \
    && install-php-extensions pdo_mysql redis gd intl bcmath zip pcntl opcache exif \
    && rm -rf /tmp/* /var/cache/apk/*

ENV TZ=America/Sao_Paulo
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Dependências PHP (sem dev)
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# ---------------------------------------------------------------------------
# Assets (Vite)
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /app
# Mesma versão do pnpm que gerou o pnpm-lock.yaml
RUN corepack enable && corepack prepare pnpm@10.27.0 --activate
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN pnpm build

# ---------------------------------------------------------------------------
# Imagem final
# ---------------------------------------------------------------------------
FROM base AS app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/production/php.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY docker/production/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/production/supervisord.conf /etc/supervisord.conf
COPY --chmod=755 docker/production/entrypoint.sh /usr/local/bin/entrypoint

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi \
    && rm /usr/bin/composer \
    && ln -sfn ../storage/app/public public/storage \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
       storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV CONTAINER_ROLE=app

EXPOSE 8080

ENTRYPOINT ["entrypoint"]
