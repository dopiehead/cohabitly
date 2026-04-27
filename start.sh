#!/bin/sh
set -e

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod --allow-no-migration

echo "Starting server on port $PORT..."
exec php -S 0.0.0.0:$PORT -t public
