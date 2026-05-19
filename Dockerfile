FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libicu-dev \
    gnupg \
    && docker-php-ext-install zip pdo_mysql intl

# Install Node.js & NPM (for Vite/Svelte build)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www/html

# Copy Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set permissions
RUN chown -R www-data:www-data /var/www/html