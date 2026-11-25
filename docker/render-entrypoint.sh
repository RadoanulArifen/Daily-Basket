#!/bin/sh
set -eu

: "${APP_KEY:?Set APP_KEY in the Render service environment.}"

mkdir -p storage/app/private storage/app/public/settings
for logo in daily-basket-logo.png daily-basket-logo-white.png; do
    if [ ! -e "storage/app/public/settings/$logo" ]; then
        cp "database/seeders/freshnest/images/$logo" "storage/app/public/settings/$logo"
        chown www-data:www-data "storage/app/public/settings/$logo"
    fi
done
chown www-data:www-data storage/app storage/app/private storage/app/public storage/app/public/settings

php-fpm -D
exec nginx -g 'daemon off;'
