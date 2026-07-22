#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link --force || true
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "WARNING: APP_KEY is empty. Set APP_KEY in EasyPanel for production."
fi

php artisan package:discover --ansi --no-interaction || true

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction || true
    php artisan view:cache --no-interaction
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
