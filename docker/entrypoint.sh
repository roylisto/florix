#!/bin/sh

# Fix permissions - use || true to ignore errors on read-only mounts or specific file ownership issues
# We only care about the core Laravel folders, not the potentially massive vendor folders inside temp ZIPs
mkdir -p /var/www/storage/app/temp /var/www/storage/app/projects /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache
chown -R www-data:www-data /var/www/storage/app /var/www/storage/framework /var/www/storage/logs /var/www/bootstrap/cache || true
chmod -R 775 /var/www/storage/app /var/www/storage/framework /var/www/storage/logs /var/www/bootstrap/cache || true

# Wait for Redis
until nc -z redis 6379; do
    echo "Waiting for Redis..."
    sleep 2
done

# Run migrations
php artisan migrate --force

# Start the process passed to the script
exec "$@"
