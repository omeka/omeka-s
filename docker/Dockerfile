FROM php:${version}-${server}

# Dependencies
RUN apt-get update && \
    apt-get install -y \
    git \
    curl \
    zlib1g-dev

# PHP configuration
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN echo "date.timezone = "Europe/London"" >> /usr/local/etc/php/conf.d/php.ini && \
    docker-php-ext-install \
    zip \
    pdo \
    pdo_mysql \
    pcntl

# Node JS.
RUN curl -sL https://deb.nodesource.com/setup_7.x | bash -
RUN apt-get install -y \
    nodejs

RUN npm install --global gulp-cli

RUN mkdir -p /var/www/html

RUN cd /var/www/html && npm install gulpjs/gulp#4.0
COPY ./package.json /var/www/html/package.json
RUN cd /var/www/html && npm install

# PHP warm
COPY ./composer.json /var/www/html/composer.json

COPY ./composer.lock /var/www/html/composer.lock

COPY ./application/data/composer-addon-installer /var/www/html/application/data/composer-addon-installer

# Webserver configuration
COPY ./docker/configs/server/scripts/installation/${configureServer}\
    /installation/${configureServer}

RUN /installation/${configureServer}

${copyConfiguration}

# Application configuration
RUN cd /var/www/html && \
    composer install --no-autoloader --no-interaction

COPY . /var/www/html

COPY ./docker/configs/database.ini /var/www/html/config/database.ini

RUN cd /var/www/html && ./node_modules/gulp/bin/gulp.js init

COPY ./docker/configs/server/scripts/runtime/${configureServer} \
     /runtime/${configureServer}

EXPOSE 80 443

WORKDIR /var/www/html

ENV OMEKA__DATABASE_HOST="127.0.0.1"
ENV OMEKA__DATABASE_NAME="omeka"
ENV OMEKA__DATABASE_PASSWORD=""
ENV OMEKA__DATABASE_PORT=3306
ENV OMEKA__DATABASE_USER="root"
ENV OMEKA__LOG_PATH="/var/log/omeka/debug.log"

RUN mkdir -p /var/log/omeka/ && \
    touch /var/log/omeka/debug.log && \
    chmod 755 /var/log/omeka/debug.log && \
    chmod -R 777 /var/www/html/files && \
    ln -sf /dev/stdout /var/log/omeka/debug.log

CMD ["/runtime/${configureServer}"]
