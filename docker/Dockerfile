FROM php:${version}-${server}

# Dependencies
RUN apt-get update && \
    apt-get install -y \
    git \
    curl \
    zlib1g-dev

# Webserver configuration
COPY ./docker/configs/server/scripts/${configureServer} /installation/${configureServer}
RUN /installation/${configureServer}

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

RUN mkdir -p /srv/omeka

RUN cd /srv/omeka && npm install gulpjs/gulp#4.0
COPY ./package.json /srv/omeka/package.json
RUN cd /srv/omeka && npm install

# PHP warm
COPY ./composer.json /srv/omeka/composer.json

COPY ./composer.lock /srv/omeka/composer.lock

COPY ./application/data/composer-addon-installer /srv/omeka/application/data/composer-addon-installer

# Application configuration
RUN cd /srv/omeka && \
    composer install --no-autoloader --no-interaction

COPY . /srv/omeka

RUN cd /srv/omeka && ./node_modules/gulp/bin/gulp.js init

COPY ./docker/configs/server/scripts/${configureServer}-runtime /runtime/${configureServer}-runtime

EXPOSE 80 443

CMD ["/runtime/${configureServer}-runtime"]
