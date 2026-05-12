# Use a modern PHP 8.3 + Nginx image
FROM serversideup/php:8.3-fpm-nginx

# Switch to root to perform installations
USER root

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy project files with correct permissions
COPY --chown=www-data:www-data . .

# Install PHP dependencies
USER www-data
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
USER root
RUN npm install && npm run build

# Ensure correct permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Deployment script
COPY --chown=www-data:www-data scripts/00-laravel-deploy.sh /var/www/html/scripts/00-laravel-deploy.sh
RUN chmod +x /var/www/html/scripts/00-laravel-deploy.sh

# The serversideup image has its own way of running things, 
# but we can tell it to run our script on startup via environment variables in Render
# or by defining it here if we were using their s6-overlay.
# For now, we'll use the Render "Start Command" or similar if needed, 
# but this image works great with default settings.
