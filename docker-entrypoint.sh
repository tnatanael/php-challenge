#!/bin/bash
set -e

# Change to the correct directory
cd /var/www/html

# Run composer update
composer update

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
MAX_TRIES=30
COUNT=0
while [ $COUNT -lt $MAX_TRIES ]; do
    if mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; then
        echo "MySQL is ready!"
        break
    fi
    echo "MySQL is not ready yet. Waiting..."
    sleep 2
    COUNT=$((COUNT+1))
done

if [ $COUNT -eq $MAX_TRIES ]; then
    echo "Error: MySQL did not become ready in time."
    exit 1
fi

# Run migrations
php ./migrate run

# Start PHP-FPM
exec php-fpm