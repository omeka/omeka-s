FROM php:8.2-cli

RUN apt-get update && apt-get install -y curl git gnupg libicu-dev libzip-dev make nano net-tools zip zlib1g-dev

RUN docker-php-ext-install intl pdo pdo_mysql zip \
    && docker-php-ext-enable intl pdo pdo_mysql zip

# install composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# add custom PHP.ini settings
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY ./custom.ini /usr/local/etc/php/conf.d/custom.ini

RUN rm -rf /var/www/html/*
WORKDIR /var/www/html/

# adds user "arc2", adds him to group "www-data" and sets his home folder
# for more background information see:
# https://medium.com/@mccode/understanding-how-uid-and-gid-work-in-docker-containers-c37a01d01cf
RUN useradd -r --home /home/arc2 -u 1000 arc2
RUN usermod -a -G www-data arc2
RUN mkdir /home/arc2
RUN chown arc2:www-data /home/arc2

EXPOSE 3306

CMD ["tail -f /dev/null"]
