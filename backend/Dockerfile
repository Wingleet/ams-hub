# Backend Dockerfile - Symfony Application
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    postgresql-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/backend

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install dependencies without scripts (scripts need env vars not available at build time)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Create var directory and set permissions
RUN mkdir -p /var/www/backend/var && \
    chown -R www-data:www-data /var/www/backend/var


# Copy and setup entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Development stage
FROM base AS development

# Install dev dependencies (without scripts - will run at container start)
RUN composer install --no-scripts --prefer-dist

# Configure PHP memory limit for development
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/memory.ini

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# Production stage
FROM base AS production

# Optimize PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
