# Use a super-lightweight PHP 8.3 + Nginx Alpine image
FROM serversideup/php:8.3-fpm-nginx-alpine

# Set working directory
WORKDIR /var/www/html

# Switch to root for installation
USER root

# Install Node.js (Alpine version is very fast to install)
RUN apk add --no-cache nodejs npm

# Copy project files
COPY --chown=www-data:www-data . .

# Install PHP dependencies as www-data
USER www-data
RUN composer install --no-dev --optimize-autoloader

# Build assets as root
USER root
RUN npm install && npm run build

# Final permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Use default serversideup entrypoint
# The image handles Nginx and PHP-FPM automatically
