# Use a PHP CLI image; for a demo free deployment we run artisan serve.
FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libxml2-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql zip gd intl opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node for Vite build
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && npm ci && npm run build && npm prune --omit=dev

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force \
    && php artisan storage:link \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
