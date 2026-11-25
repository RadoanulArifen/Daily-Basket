# PHP-FPM with the extensions Laravel and this shop need, plus a front-end build.
FROM node:22-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM php:8.3-fpm-alpine

RUN apk add --no-cache libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip intl opcache bcmath exif

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

COPY . .
COPY --from=assets /build/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
