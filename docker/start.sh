#!/bin/sh

# Railway assigns a dynamic PORT — default to 80 if not set
PORT="${PORT:-80}"

# Inject the port into nginx config
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/http.d/default.conf

# Run migrations automatically on deploy
php /var/www/artisan migrate --force 2>&1 || true

# Cache config and routes for performance
php /var/www/artisan config:cache 2>&1
php /var/www/artisan route:cache 2>&1
php /var/www/artisan view:cache 2>&1

# Create storage link
php /var/www/artisan storage:link 2>&1 || true

# Start supervisor (runs php-fpm + nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
