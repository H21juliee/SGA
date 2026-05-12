#!/bin/sh

# Run migrations
php artisan migrate --force

# Run seeders (only for first deploy, but --force handles it safely)
php artisan db:seed --force
