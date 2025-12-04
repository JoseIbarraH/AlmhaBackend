# Imagen base con PHP 8.3 CLI
FROM php:8.3-cli AS base

# Instala dependencias del sistema, Nginx y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev libcurl4-openssl-dev libssl-dev \
    zlib1g-dev libicu-dev g++ libevent-dev procps \
    libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
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

# Copia configuración de Nginx
COPY nginx.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configuración de Supervisor para ejecutar Nginx y Octane juntos
RUN echo '[supervisord]\n\
    nodaemon=true\n\
    user=root\n\
    \n\
    [program:nginx]\n\
    command=/usr/sbin/nginx -g "daemon off;"\n\
    autostart=true\n\
    autorestart=true\n\
    stdout_logfile=/dev/stdout\n\
    stdout_logfile_maxbytes=0\n\
    stderr_logfile=/dev/stderr\n\
    stderr_logfile_maxbytes=0\n\
    \n\
    [program:octane]\n\
    command=php /var/www/artisan octane:start --server=swoole --host=127.0.0.1 --port=9000\n\
    directory=/var/www\n\
    autostart=true\n\
    autorestart=true\n\
    stdout_logfile=/dev/stdout\n\
    stdout_logfile_maxbytes=0\n\
    stderr_logfile=/dev/stderr\n\
    stderr_logfile_maxbytes=0\n\
    ' > /etc/supervisor/conf.d/supervisord.conf

# Expone el puerto 80 (Nginx)
EXPOSE 80

# Comando de inicio con Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
