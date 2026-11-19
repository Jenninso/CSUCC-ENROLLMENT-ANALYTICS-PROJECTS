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

# Boot: migrate (includes one-shot synthetic wipe), optional CLEAR_RENDER_DATA wipe,
# then seed roles/users/CEIT scope only — never demo students on Render.
CMD sh -c "php artisan migrate --force && php artisan storage:link --force && if [ \"\$CLEAR_RENDER_DATA\" = \"true\" ] || [ \"\$CLEAR_RENDER_DATA\" = \"1\" ]; then php artisan app:wipe-data --force || true; fi && (php artisan db:seed --force || true) && php artisan serve --host=0.0.0.0 --port=\${PORT:-8000}"
