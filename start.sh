#!/bin/sh
set -e

# Rewrite mysql:// -> mysqli:// so Doctrine uses the mysqli driver
# which supports caching_sha2_password (MySQL 8 default auth)
if [ -n "$DATABASE_URL" ]; then
    export DATABASE_URL=$(echo "$DATABASE_URL" | sed 's|^mysql://|mysqli://|')
fi

echo "DATABASE_URL scheme: $(echo $DATABASE_URL | cut -d: -f1)"
echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod --allow-no-migration

echo "Starting server on port $PORT..."
exec php -S 0.0.0.0:$PORT -t public
