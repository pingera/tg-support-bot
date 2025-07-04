# --- Stage 1: Build vendor dependencies ---
FROM composer:2 as vendor

WORKDIR /app

# Copy only composer files to leverage Docker cache
COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock

# Install dependencies
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader


# --- Stage 2: Build the final application image ---
FROM php:8.3-fpm

USER root

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Copy custom php.ini configuration
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www

# Copy vendor directory from the build stage
COPY --from=vendor /app/vendor/ /var/www/vendor/

# Copy the rest of the application files
COPY . .

# Set correct permissions for storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Cache Laravel configurations
# This is a major performance boost and highly recommended for production.
# Artisan will use the runtime environment variables when caching.
RUN php artisan route:cache && \
    php artisan view:cache

# Switch to non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
