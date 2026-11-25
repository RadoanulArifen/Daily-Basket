FROM php:8.3-fpm-alpine AS php-deps

RUN apk add --no-cache freetype icu-libs libjpeg-turbo libpng libpq libzip nginx \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libpq-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_pgsql gd zip intl opcache bcmath exif \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

FROM node:22-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY app ./app
COPY --from=php-deps /var/www/html/vendor ./vendor
RUN npm run build

FROM php-deps AS runtime

# This image runs on one Render instance by default. Multi-instance deployments can
# override CACHE_STORE with a shared store. Sessions retain their own configuration.
ENV CACHE_STORE=file

COPY --from=assets /build/public/build ./public/build
COPY docker/render.nginx.conf /etc/nginx/http.d/default.conf
COPY docker/render-entrypoint.sh /usr/local/bin/daily-basket-start
RUN printf '[www]\nlisten = 127.0.0.1:9000\n' > /usr/local/etc/php-fpm.d/zzz-render.conf \
    && printf 'opcache.validate_timestamps=0\n' > /usr/local/etc/php/conf.d/zzz-render-opcache.ini \
    && chmod 755 /usr/local/bin/daily-basket-start \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -s ../storage/app/public public/storage

EXPOSE 10000
CMD ["daily-basket-start"]
