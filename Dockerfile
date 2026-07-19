# ── Coolify Detection Labels ─────────────────────────────────────
# These labels tell Coolify how to build and run this app
LABEL coolify.managed=true
LABEL coolify.name="erp-universal"
LABEL coolify.description="ERP Universal — Sistema de gestão empresarial multi-tenant"

# ── Full Dockerfile for Coolify ──────────────────────────────────
# Coolify v4.1.2 supports Dockerfile builds via "build_pack: dockerfile"

FROM php:8.3-fpm-alpine

# ── System dependencies ──────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    zip unzip git curl \
    icu-dev oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# ── PHP extensions ───────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pdo_mysql \
        bcmath intl mbstring opcache zip gd \
    && pecl install redis \
    && docker-php-ext-enable redis

# ── Composer ─────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── App code ─────────────────────────────────────────────────────
COPY . .

# ── Dependencies ─────────────────────────────────────────────────
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Nginx config ─────────────────────────────────────────────────
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
RUN mkdir -p /run/nginx && chown -R www-data:www-data /run/nginx

# ── PHP config ───────────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-erp.ini

# ── Supervisor config (nginx + php-fpm) ──────────────────────────
RUN mkdir -p /var/log/supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
