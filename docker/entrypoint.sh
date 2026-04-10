#!/bin/sh

# Fix permissions
mkdir -p /var/www/storage/app/temp
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Wait for Redis
until nc -z redis 6379; do
    echo "Waiting for Redis..."
    sleep 2
done

# Run migrations
php artisan migrate --force

# Start the process passed to the script
exec "$@"
