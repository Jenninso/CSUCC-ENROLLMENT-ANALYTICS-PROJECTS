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

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
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
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/start.sh

# Boot: migrate, optional wipe, seed, then Apache (handles large uploads better than artisan serve).
CMD ["docker/start.sh"]
