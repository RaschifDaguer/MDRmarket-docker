#!/bin/sh

PORT="${PORT:-80}"

echo "Starting with PORT=$PORT"

mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/logs
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage

sed -i "s/listen 80;/listen $PORT;/" /etc/nginx/http.d/default.conf

php /var/www/artisan migrate --force 2>&1 || true

php /var/www/artisan config:cache 2>&1
php /var/www/artisan route:cache 2>&1
php /var/www/artisan view:cache 2>&1

php /var/www/artisan storage:link 2>&1 || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
