# syntax=docker/dockerfile:1.7

# ---------- Etapa 1: assets (Node) ----------
FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.* tsconfig*.json ./
COPY routes ./routes
COPY app ./app

RUN npm run build

# ---------- Etapa 2: vendor (Composer) ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ---------- Etapa 3: runtime (PHP) ----------
FROM php:8.3-cli-bookworm AS runtime

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        zlib1g-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        bcmath \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN chmod +x start.sh \
    && mkdir -p storage/app/public \
                storage/framework/cache \
                storage/framework/sessions \
                storage/framework/views \
                storage/logs \
                bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["bash", "start.sh"]
