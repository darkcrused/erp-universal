FROM php:8.3-cli-bookworm

LABEL coolify.managed=true
LABEL coolify.name="erp-universal"
LABEL coolify.description="ERP Universal — Sistema de gestão empresarial multi-tenant"

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpq-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        mbstring \
        intl \
        bcmath \
        xml \
        exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN mkdir -p bootstrap/cache storage/framework/{cache,data,sessions,views} \
    && composer install --no-dev --no-interaction --optimize-autoloader --no-progress \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=80"]
