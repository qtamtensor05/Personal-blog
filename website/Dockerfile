FROM node:24-alpine AS assets

WORKDIR /src

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY app ./app
COPY vite.config.js ./
RUN npm run build


FROM composer:2 AS composer


FROM php:8.4-cli-alpine AS vendor

RUN apk add --no-cache icu-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl zip \
    && apk del $PHPIZE_DEPS

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /src

COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader


FROM php:8.4-fpm-alpine AS application

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl mbstring pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

RUN printf '%s\n' \
    'upload_max_filesize=12M' \
    'post_max_size=16M' \
    > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

COPY --from=vendor /src/vendor ./vendor
COPY . .
COPY --from=assets /src/public/build ./public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

CMD ["php-fpm", "-F"]


FROM nginx:1.29-alpine AS web

COPY --from=application /var/www/html/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
