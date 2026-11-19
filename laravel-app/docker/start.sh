#!/bin/sh
set -e

PORT="${PORT:-80}"

# Render injects $PORT; Apache must listen there (not only 80).
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf || true
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true

# Keep the site up even if migrate/seed hiccups after a successful prior deploy.
php artisan migrate --force || true
php artisan storage:link --force || true

if [ "$CLEAR_RENDER_DATA" = "true" ] || [ "$CLEAR_RENDER_DATA" = "1" ]; then
  php artisan app:wipe-data --force || true
fi

php artisan db:seed --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec apache2-foreground
