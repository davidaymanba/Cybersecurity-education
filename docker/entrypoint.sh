#!/bin/sh
# Prepare storage, run migrations and warm Laravel's caches before starting Apache.
set -e
cd /var/www/html

as_app() {
    if [ "$(id -u)" = "0" ]; then su www-data -s /bin/sh -c "$1"; else sh -c "$1"; fi
}

# The storage volume starts empty on first boot: recreate Laravel's directory layout.
mkdir -p storage/app/public/audio storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs
chown -R www-data:www-data storage

# public/storage -> storage/app/public (generated TTS audio).
[ -L public/storage ] || ln -s /var/www/html/storage/app/public public/storage

if [ "$RUN_MIGRATIONS" = "1" ]; then
    as_app "php artisan migrate --force"
fi

as_app "php artisan optimize"

exec "$@"
