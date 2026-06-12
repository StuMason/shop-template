# ============================================
# Stage 1: PHP Dependencies
# ============================================
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# ============================================
# Frontend Build Stage
# ============================================
# Uses PHP+Node image so Laravel Vite plugins (e.g., Wayfinder) can run artisan commands
FROM ghcr.io/stumason/laravel-coolify-base:8.4-node AS frontend-build

WORKDIR /app

# Copy composer binary from composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP dependencies (enables artisan commands in Vite plugins)
COPY composer.json composer.lock ./
COPY database ./database
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Node dependencies
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Copy source and build (client + SSR bundle)
COPY . .
RUN composer dump-autoload --optimize
RUN npm run build:ssr

# ============================================
# Stage 2: Production Image
# ============================================
# Using pre-built base image with PHP extensions already compiled.
# This reduces build time from ~12 minutes to ~2-3 minutes.
# To build from scratch instead, set COOLIFY_USE_BASE_IMAGE=false
FROM ghcr.io/stumason/laravel-coolify-base:8.4-node AS production

LABEL maintainer="Laravel Coolify" \
      description="Laravel application deployed via Coolify"

# Custom PHP config
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-custom.ini"

# PHP-FPM config (enables container log forwarding)
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-laravel.conf

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copy composer binary and dependencies from build stages
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=composer-deps /app/vendor ./vendor

# Copy application code (order matters for caching!)
COPY artisan ./
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY --from=frontend-build /app/public/build ./public/build
COPY --from=frontend-build /app/bootstrap/ssr ./bootstrap/ssr
COPY routes ./routes
COPY storage ./storage
COPY resources/views ./resources/views
COPY app ./app
COPY composer.json composer.lock ./

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint configuration
ENV AUTO_MIGRATE=true \
    AUTO_SEED=false \
    DB_WAIT_TIMEOUT=30

# Entrypoint script (runs migrations + optimize on startup)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh


HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]