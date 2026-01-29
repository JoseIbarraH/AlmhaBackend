FROM php:8.3-cli AS base

# ------------------------
# Dependencias del sistema
# ------------------------
RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev libcurl4-openssl-dev libssl-dev \
    zlib1g-dev libicu-dev g++ libevent-dev procps \
    libfreetype6-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath sockets intl

# ------------------------
# Swoole
# ------------------------
RUN curl -L -o swoole.tar.gz https://github.com/swoole/swoole-src/archive/refs/tags/v5.1.0.tar.gz \
    && tar -xf swoole.tar.gz \
    && cd swoole-src-5.1.0 \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable swoole

# ------------------------
# Composer
# ------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ------------------------
# Composer deps (cacheable)
# ------------------------
COPY composer.json composer.lock artisan ./
RUN mkdir -p bootstrap/cache storage/app storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ------------------------
# Proyecto completo
# ------------------------
COPY . .

RUN composer dump-autoload --optimize

RUN php artisan storage:link || true

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

# ------------------------
# Entrypoint inteligente
# ------------------------
RUN echo '#!/bin/sh\n\
set -e\n\
\n\
php artisan config:clear\n\
php artisan route:clear\n\
php artisan view:clear\n\
\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
if [ "$APP_ROLE" = "worker" ]; then\n\
  echo \"🚀 Starting Laravel Queue Worker\";\n\
  exec php artisan queue:work redis --sleep=3 --tries=3 --timeout=90;\n\
else\n\
  echo \"🚀 Starting Laravel Octane (Swoole)\";\n\
  exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=9000;\n\
fi\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
