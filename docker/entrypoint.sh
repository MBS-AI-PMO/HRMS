#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ ! -f public/index.php ]; then
    cp -r docker/public/. public/
fi

if [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

php artisan package:discover --ansi >/dev/null 2>&1 || true

if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

exec "$@"
