#!/bin/sh
set -e

echo "==> Starting container initialization..."

# Fix storage & cache permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink if missing
if [ ! -L /var/www/html/public/storage ]; then
    echo "==> Creating storage symlink..."
    php artisan storage:link || true
fi

# Run database migrations if DB is configured
if [ -n "$DB_HOST" ] || [ "$DB_CONNECTION" = "sqlite" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force || echo "Warning: Migration failed or database not ready yet."
fi

# Optimize Laravel cache for production if in production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching configuration and routes for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    echo "==> Clearing any stale cache..."
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

echo "==> Starting web services..."
exec "$@"
