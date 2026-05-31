FROM node:18-bullseye AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY webpack.mix.js tailwind.config.js ./
RUN npm run prod

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Upstream lsky-pro registers some require-dev providers unconditionally, so the
# production image must keep dev packages until the legacy provider list is cleaned.
RUN composer install --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    --ignore-platform-req=php \
    --ignore-platform-req=ext-ftp

FROM php:8.0-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libssl-dev \
        libmagickwand-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif ftp gd mbstring pdo_mysql zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public ./public
COPY .docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R ug+rw storage bootstrap/cache

EXPOSE 80
