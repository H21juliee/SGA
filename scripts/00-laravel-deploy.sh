#!/usr/bin/env bash
echo "Running deployment scripts..."

# Ensure storage and bootstrap/cache are writable
chmod -R 777 storage bootstrap/cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration and routes
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment scripts completed!"
