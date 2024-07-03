FROM php:apache

ENV APACHE_RUN_USER=www-data

ENV APACHE_RUN_GROUP=www-data

ENV APACHE_RUN_DIR=/var/run/apache2

ENV APACHE_LOG_DIR=/var/log/apache2

ENV APACHE_LOCK_DIR=/var/lock/apache2

ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid

RUN apt update && apt install -y git zip unzip libzip-dev libicu-dev nodejs npm

RUN git clone https://github.com/omeka/omeka-s .

RUN npm install

RUN docker-php-ext-install pdo_mysql intl

RUN npx gulp init

RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html/

COPY docker-entrypoint.sh .

VOLUME /var/www/html/files

CMD ["apache2", "-D", "FOREGROUND"]

ENTRYPOINT ["./docker-entrypoint.sh"]
