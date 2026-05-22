FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql sockets

RUN pecl install pcov && docker-php-ext-enable pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Remove a pasta vendor e cache do Mac copiadas acidentalmente
RUN rm -rf vendor composer.lock bootstrap/cache/*.php

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader

RUN php artisan optimize:clear || true

EXPOSE 8000
