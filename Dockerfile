# Use an optimized PHP 8.3 + Nginx image
FROM richarvey/nginx-php-fpm:php8.3

# Set working directory
WORKDIR /var/www/html

# Allow composer to run as superuser
ENV COMPOSER_ALLOW_SUPERUSER 1

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
RUN apk add --no-cache nodejs npm \
    && npm install \
    && npm run build

# Configure Nginx and PHP
ENV NGINX_WEBROOT /var/www/html/public
ENV PHP_UPLOAD_MAX_FILESIZE 100M
ENV PHP_POST_MAX_SIZE 100M

# Script to run on container start
COPY scripts/00-laravel-deploy.sh /var/www/html/scripts/00-laravel-deploy.sh
RUN chmod +x /var/www/html/scripts/00-laravel-deploy.sh

# The richarvey image uses this env var to run scripts at boot
ENV RUN_SCRIPTS 1
