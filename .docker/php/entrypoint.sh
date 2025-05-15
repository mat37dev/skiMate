#!/bin/sh
set -e

mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R 775 var

mkdir -p /var/www/symfony/public/uploads/logos
chown -R www-data:www-data /var/www/symfony/public/uploads
chmod -R 775 /var/www/symfony/public/uploads

exec "$@"
