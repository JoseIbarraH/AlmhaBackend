FROM php:8.3-cli AS base

# Instala dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev libcurl4-openssl-dev libssl-dev \
    zlib1g-dev libicu-dev g++ libevent-dev procps \
    libfreetype6-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath sockets intl

# Instala Swoole desde GitHub
RUN curl -L -o swoole.tar.gz https://github.com/swoole/swoole-src/archive/refs/tags/v5.1.0.tar.gz \
    && tar -xf swoole.tar.gz \
    && cd swoole-src-5.1.0 \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable swoole

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define el directorio de trabajo
WORKDIR /var/www

# Copia los archivos esenciales primero
COPY composer.json composer.lock artisan ./

# Crea las carpetas básicas de Laravel
RUN mkdir -p bootstrap/cache storage/app storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs

# Instala dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copia el resto del proyecto
COPY . .

# Ejecuta los post-scripts de Composer (dump-autoload, etc.)
RUN composer dump-autoload --optimize

# Limpia y genera caches
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Asigna permisos correctos
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expone el puerto 9000 (usado por Octane/Swoole)
EXPOSE 9000

# Script de inicio
RUN echo '#!/bin/bash\n\
    php artisan config:cache\n\
    php artisan route:cache\n\
    php artisan view:cache\n\
    exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=9000\n\
    ' > /start.sh && chmod +x /start.sh

# Comando de inicio por defecto
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan octane:start --server=swoole --host=0.0.0.0 --port=9000"]