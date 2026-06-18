# ---- Build stage: Composer ----
FROM composer:latest AS composer-build

WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist

COPY . .
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# ---- Build stage: Node ----
FROM node:22-alpine AS node-build

WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Runtime ----
FROM php:8.4-fpm-alpine

# Install system packages
# Install runtime system packages (libpq kept for pdo_pgsql runtime)
RUN apk add --no-cache \
    bash \
    nginx \
    curl \
    libpq

# Install build deps for pdo_pgsql, compile extensions, then strip dev packages
RUN apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql \
    && apk del .build-deps

# Install Composer
COPY --from=composer-build /usr/bin/composer /usr/bin/composer

# Application
WORKDIR /var/www/html

COPY --from=composer-build /build .
COPY --from=node-build   /build/public/build ./public/build

# Deployment configs
COPY deploy/nginx.conf /etc/nginx/nginx.conf
COPY deploy/start.sh   ./deploy/start.sh

# Permissions
RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/public/build \
    && chmod +x deploy/start.sh

# App config
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 80

CMD ["./deploy/start.sh"]
