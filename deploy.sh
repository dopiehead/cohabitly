#!/bin/bash

echo "🚀 Starting Symfony deployment..."

# Stop on error
set -e

# --- CONFIG ---
APP_DIR="/Users/mac/cohabit"
PHP_BIN="/usr/local/bin/php"
COMPOSER_BIN="/usr/local/bin/composer"

# --- ENV ---
export APP_ENV=prod
export APP_DEBUG=0

cd $APP_DIR

echo "📦 Pulling latest code..."
git pull origin main

echo "📦 Installing dependencies (prod only)..."
$COMPOSER_BIN install \
  --no-dev \
  --no-scripts \
  --optimize-autoloader \
  --prefer-dist

echo "🧹 Clearing cache..."
$PHP_BIN bin/console cache:clear --env=prod

echo "🔥 Warming cache..."
$PHP_BIN bin/console cache:warmup --env=prod

echo "🗄 Running migrations..."
$PHP_BIN bin/console doctrine:migrations:migrate --no-interaction || true

echo "🔐 Fixing permissions..."
chown -R $(whoami):staff var public
chmod -R 775 var

echo "✅ Deployment completed successfully!"

