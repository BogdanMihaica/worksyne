FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
        git \
        unzip \
        libzip-dev \
    && useradd --uid 1000 --create-home --shell /bin/sh app \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY _deploy/cron/laravel-scheduler /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler

COPY api/ ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 9000
