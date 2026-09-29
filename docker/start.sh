#!/bin/sh
# Preview entrypoint: apply migrations and the seed (both idempotent), cache the configuration, then serve HTTP.
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

attempt=1
until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge 30 ]; then
        echo "database not reachable; giving up" >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    sleep 2
done

php artisan db:seed --force --no-interaction
php artisan optimize --no-interaction

cd public
exec php -S "0.0.0.0:${PORT:-8080}" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
