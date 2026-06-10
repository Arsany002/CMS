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

# Create storage symlink
php artisan storage:link --quiet || true

# Start supervisord (manages php-fpm + nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
