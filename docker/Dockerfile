FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip

RUN { \
    echo "error_reporting = E_ALL"; \
    echo "display_errors = On"; \
    echo "display_startup_errors = On"; \
    } > /usr/local/etc/php/conf.d/docker-php-custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN chown -R www-data:www-data /var/www/html/var/cache /var/www/html/var/log

RUN composer install --no-scripts --prefer-dist --no-dev --optimize-autoloader

EXPOSE 9000
