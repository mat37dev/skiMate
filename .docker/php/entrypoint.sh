#!/bin/sh
set -e

mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R 775 var

exec "$@"
