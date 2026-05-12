# Use a super-lightweight PHP 8.3 + Nginx Alpine image
FROM serversideup/php:8.3-fpm-nginx-alpine

# Set working directory
WORKDIR /var/www/html

# Switch to root for installation
USER root

# Install Node.js
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

# --- NEW: Automatic Migrations ---
# Serversideup image executes everything in /etc/entrypoint.d/ at startup
COPY --chmod=755 scripts/init.sh /etc/entrypoint.d/99-init.sh
# ---------------------------------

USER www-data
