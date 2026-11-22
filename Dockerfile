# syntax=docker/dockerfile:1
FROM node:24-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.ts tsconfig.json ./
COPY resources/js resources/js
COPY resources/css resources/css
ARG VITE_REVERB_APP_KEY=kanban-local
ARG VITE_REVERB_HOST=
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http
RUN npm run build

FROM composer:2 AS composer-bin
FROM caddy:2-alpine AS caddy-bin

FROM php:8.3-cli-bookworm AS php
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql pcntl \
    && pecl install pcov-1.0.12 \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'pcov.directory=/app/app\n' > "$PHP_INI_DIR/conf.d/pcov-coverage.ini"

FROM php AS dependencies
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

FROM php AS app
WORKDIR /app
ENV APP_DEBUG=false
COPY --chown=www-data:www-data . .
COPY --from=dependencies --chown=www-data:www-data /app/vendor /app/vendor
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY --from=frontend /app/public/build /app/public/build
COPY --from=caddy-bin /usr/bin/caddy /usr/local/bin/caddy
RUN touch .env
USER www-data
RUN composer dump-autoload --optimize \
    && php artisan l5-swagger:generate
EXPOSE 8000
HEALTHCHECK --interval=15s --timeout=3s --start-period=10s \
    CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:8000/up') === false ? 1 : 0);"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
