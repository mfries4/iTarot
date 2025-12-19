# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.2
ARG APP_ENV=prod
ARG APP_SECRET=ChangeMeInProd

FROM composer:2 AS vendor

ENV APP_ENV=${APP_ENV} \
    APP_SECRET=${APP_SECRET}

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM php:${PHP_VERSION}-apache AS app

ENV APP_ENV=${APP_ENV} \
    APP_SECRET=${APP_SECRET} \
    APP_DEBUG=0 \
    APACHE_DOCUMENT_ROOT=/var/www/app/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl opcache pdo pdo_pgsql \
    && a2enmod rewrite \
    && sed -ri "s#/var/www/html#${APACHE_DOCUMENT_ROOT}#g" \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/sites-available/default-ssl.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/app

COPY --from=vendor /app /var/www/app

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

EXPOSE 80

CMD ["apache2-foreground"]
