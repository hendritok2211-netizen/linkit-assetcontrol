#!/usr/bin/env bash
# exit on error
set -o errexit

# Install PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies
npm ci --include=dev

# Build assets
npm run build

# Clear and cache config
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link (if needed)
php artisan storage:link || true

echo "Build completed successfully!"
