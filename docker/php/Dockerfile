FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev libzip-dev git unzip \
    && docker-php-ext-install intl pdo_mysql zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app
