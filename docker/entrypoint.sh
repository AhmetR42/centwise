#!/bin/sh
set -e

# First start: create the environment file, app key and SQLite database.
if [ ! -f .env ]; then
    cp .env.example .env
    sed -i 's/^APP_ENV=.*/APP_ENV=production/; s/^APP_DEBUG=.*/APP_DEBUG=false/' .env
    php artisan key:generate --force
fi

mkdir -p database/data
export DB_DATABASE="${DB_DATABASE:-/app/database/data/database.sqlite}"

# "artisan serve" does not pass every environment variable to the web server,
# so the database path must also be written to .env.
if grep -q '^DB_DATABASE=' .env; then
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env
else
    echo "DB_DATABASE=$DB_DATABASE" >> .env
fi

if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    php artisan migrate --seed --force
else
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache

exec "$@"
