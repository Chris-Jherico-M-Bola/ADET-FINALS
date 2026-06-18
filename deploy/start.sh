#!/usr/bin/env bash
set -e

echo "Clearing config cache..."
php artisan config:clear

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force || echo "Migration failed — will retry on next deploy"

echo "Starting PHP-FPM..."
php-fpm --nodaemonize &

echo "Starting NGINX..."
exec /usr/sbin/nginx -g 'daemon off;'
