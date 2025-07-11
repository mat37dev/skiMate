#!/bin/sh
set -e

# 🔑 Installer les vendors si absents
cd /var/www/symfony
if [ ! -d "vendor" ]; then
    echo "🚀 Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Vendors already installed, skipping composer install"
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R 775 var

mkdir -p /var/www/symfony/public/uploads/logos
chown -R www-data:www-data /var/www/symfony/public/uploads
chmod -R 775 /var/www/symfony/public/uploads

exec "$@"
