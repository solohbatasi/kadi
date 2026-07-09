#!/bin/sh
set -e

cd /app

mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

prepare_laravel() {
    if [ "${APP_ENV}" != "local" ]; then
        php artisan optimize:clear --no-interaction
        php artisan config:cache --no-interaction
        php artisan route:cache --no-interaction
        php artisan view:cache --no-interaction
    fi

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        php artisan migrate --force --no-interaction
    fi

    if [ "${RUN_ADMIN_SEEDER:-false}" = "true" ]; then
        php artisan db:seed --class=AdminUserSeeder --force --no-interaction
    fi
}

case "$1" in
    app)
        prepare_laravel
        exec frankenphp run --config /etc/caddy/Caddyfile
        ;;
    queue)
        prepare_laravel
        exec php artisan queue:work \
            --queue="${QUEUE_NAMES:-webhooks,payouts,default}" \
            --tries="${QUEUE_TRIES:-3}" \
            --timeout="${QUEUE_TIMEOUT:-120}" \
            --memory="${QUEUE_MEMORY:-256}" \
            --sleep="${QUEUE_SLEEP:-3}" \
            --no-interaction
        ;;
    scheduler)
        prepare_laravel
        while true; do
            php artisan schedule:run --verbose --no-interaction
            sleep "${SCHEDULER_SLEEP:-60}"
        done
        ;;
    artisan)
        shift
        exec php artisan "$@"
        ;;
    *)
        exec "$@"
        ;;
esac
