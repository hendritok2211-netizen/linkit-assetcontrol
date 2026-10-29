#!/usr/bin/env bash
# Exit on error
set -o errexit

echo "🚀 Starting build process..."

# 1. Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 2. Install Node dependencies
echo "📦 Installing NPM dependencies..."
npm ci --include=dev

# 3. Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Check if build succeeded
if [ ! -d "public/build" ]; then
  echo "❌ ERROR: Vite build failed! public/build directory not found."
  exit 1
fi

echo "✅ Assets built successfully!"

# 4. Clear old cache
echo "🧹 Clearing old cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Optimize for production
echo "⚡ Optimizing for production..."
php artisan optimize

# 6. Optimize autoloader
echo "🔧 Optimizing autoloader..."
composer dump-autoload --optimize --classmap-authoritative

echo "🎉 Build completed successfully!"
