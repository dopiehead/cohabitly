#!/bin/sh
set -e

# Force mysqli:// scheme so Doctrine uses mysqli driver (supports caching_sha2_password)
if [ -n "$DATABASE_URL" ]; then
    export DATABASE_URL=$(echo "$DATABASE_URL" | sed 's|^mysql://|mysqli://|' | sed 's|^pdo-mysql://|mysqli://|')
fi

echo "Scheme in use: $(echo $DATABASE_URL | cut -d: -f1)"

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod --allow-no-migration || echo "Migrations failed, continuing..."

echo "Starting PHP server on port ${PORT:-8000}..."
exec php -S 0.0.0.0:${PORT:-8000} -t public
