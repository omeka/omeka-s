FROM composer:2 AS vendor

WORKDIR /app

COPY . .

RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-reqs \
    && for f in .htaccess.dist config/*.dist logs/*.dist; do cp "$f" "${f%.dist}"; done

FROM php:8.3-apache

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql intl imagick \
    && apt-get update \
    && apt-get install -y --no-install-recommends imagemagick \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'upload_max_filesize = 25M\npost_max_size = 25M\n' > "$PHP_INI_DIR/conf.d/uploads.ini"

COPY --from=vendor --chown=www-data:www-data /app /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/

VOLUME /var/www/html/files

ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["apache2-foreground"]
