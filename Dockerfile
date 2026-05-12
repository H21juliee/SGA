# Use an optimized PHP + Nginx image
FROM richarvey/nginx-php-fpm:latest

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install system dependencies if needed (most are included in richarvey image)
# RUN apk add --no-cache ...

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
# We'll use a custom script for migrations and caching
COPY scripts/00-laravel-deploy.sh /var/www/html/scripts/00-laravel-deploy.sh
RUN chmod +x /var/www/html/scripts/00-laravel-deploy.sh

# The richarvey image uses this env var to run scripts at boot
ENV RUN_SCRIPTS 1
