FROM php:8.3-apache

RUN apt-get update && apt-get install -y git unzip libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Large CEIT CSV/Excel imports (app cap 200 MB; PHP set above that for headroom).
RUN printf '%s\n' \
    'upload_max_filesize=256M' \
    'post_max_size=256M' \
    'memory_limit=1024M' \
    'max_execution_time=0' \
    'max_input_time=600' \
    > /usr/local/etc/php/conf.d/uploads.ini

COPY docker/apache-uploads.conf /etc/apache2/conf-available/uploads.conf
RUN a2enconf uploads

# Point Apache at Laravel's public/ folder (use real paths — ENV vars are NOT expanded inside single-quoted sed).
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
        '    Options FollowSymLinks' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        >> /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/start.sh

# Boot: migrate, optional wipe, seed, then Apache (handles large uploads better than artisan serve).
CMD ["docker/start.sh"]
