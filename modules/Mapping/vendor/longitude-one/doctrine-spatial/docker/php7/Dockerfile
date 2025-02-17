FROM php:7-fpm-alpine

RUN apk --update --no-cache add bash git postgresql-dev mysql-dev  \
        && docker-php-ext-install pdo_pgsql pdo_mysql  \
        && docker-php-ext-enable pdo_pgsql pdo_mysql

#Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##Install symfony
RUN wget https://get.symfony.com/cli/installer -O /tmp/installer && \
    chmod a+x /tmp/installer && \
    /tmp/installer --install-dir=/usr/local/bin/ && \
    rm /tmp/installer

WORKDIR /var/www

CMD composer install ;  php-fpm
EXPOSE 9000
