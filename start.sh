#!/bin/sh
set -e

# Force mysqli driver
if [ -n "$DATABASE_URL" ]; then
    export DATABASE_URL=$(echo "$DATABASE_URL" | sed 's|^mysql://|mysqli://|' | sed 's|^pdo-mysql://|mysqli://|')
fi

echo "Warming cache..."
php bin/console cache:clear --env=prod --no-debug --no-interaction
php bin/console cache:warmup --env=prod --no-debug --no-interaction

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod --allow-no-migration || echo "Migrations failed, continuing..."

echo "Starting server on port ${PORT:-8000}..."
exec php -S 0.0.0.0:${PORT:-8000} -t public
