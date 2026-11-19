FROM php:8.3-cli

RUN apt-get update && apt-get install -y git unzip libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Keep boot fast for Render. Optional one-shot wipe: CLEAR_RENDER_DATA=true
# Optional CEIT import after wipe: CEIT_FORCE_IMPORT=true (slow; avoid on first health check if possible)
CMD sh -c "php artisan migrate --force && php artisan storage:link --force && if [ \"\$CLEAR_RENDER_DATA\" = \"true\" ] || [ \"\$CLEAR_RENDER_DATA\" = \"1\" ]; then php artisan app:wipe-data --force || true; fi && (php artisan db:seed --force || true) && php artisan serve --host=0.0.0.0 --port=\${PORT:-8000}"
