# Stage 1: Construcción
FROM php:8.2-fpm-alpine AS build

# Variables de entorno
ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

# Instalación de dependencias del sistema
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    npm \
    nodejs \
    yarn \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo_mysql \
        zip \
    && apk del icu-dev libzip-dev

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar dependencias y código fuente
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY . .

# Stage 2: Producción
FROM php:8.2-fpm-alpine AS production

WORKDIR /app

# Copiar dependencias instaladas desde build
COPY --from=build /app /app

# Copiar archivos necesarios para Laravel
COPY --from=build /usr/local/etc/php/php.ini /usr/local/etc/php/php.ini

# Crear usuario www-data
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

RUN chown -R www:www /app \
    && chmod -R 755 /app

# Exponer puerto PHP-FPM
EXPOSE 9000

USER www

CMD ["php-fpm"]
