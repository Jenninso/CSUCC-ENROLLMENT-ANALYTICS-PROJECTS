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

CMD sh -c "php artisan migrate --force && php artisan storage:link --force && php artisan db:seed --class=StudentRecordSeeder --force && php artisan db:seed --class=SprSyntheticSeeder --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"
