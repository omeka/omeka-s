#!/usr/bin/env bash
composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
php-fpm &
nginx -g 'daemon off;'
