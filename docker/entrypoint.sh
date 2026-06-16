#!/bin/sh
set -e

# Wait is handled by Docker healthchecks + depends_on

# Run migrations (idempotent in production)
php artisan migrate --force

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Generate Passport keys if missing, then enforce 600 permissions
php artisan passport:keys --no-interaction --quiet || true
find /var/www/html/storage -name "*.key" -exec chmod 600 {} \; 2>/dev/null || true

# Create storage symlink
php artisan storage:link --quiet || true

# Start supervisord (manages php-fpm + nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
