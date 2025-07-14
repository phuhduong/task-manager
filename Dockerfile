FROM php:8.3-apache

# Install extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql

# Composer install
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy source
COPY . .

# Install PHP dependencies
RUN composer install

EXPOSE 80
