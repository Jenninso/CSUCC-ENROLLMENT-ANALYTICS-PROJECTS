#!/bin/sh
set -e

PORT="${PORT:-80}"

# Render injects $PORT; Apache must listen there (not only 80).
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
if grep -q '<VirtualHost \*:80>' /etc/apache2/sites-available/000-default.conf; then
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

php artisan migrate --force
php artisan storage:link --force || true

if [ "$CLEAR_RENDER_DATA" = "true" ] || [ "$CLEAR_RENDER_DATA" = "1" ]; then
  php artisan app:wipe-data --force || true
fi

php artisan db:seed --force || true

exec apache2-foreground
