#!/usr/bin/env bash
echo -e "\033[00;33m==> Configuring Nginx and PHP-FPM.\033[0m";

# nginx
apt-get install -y \
        nginx  && \
        echo "" > /etc/nginx/sites-available/default && \
        ln -sf /dev/stdout /var/log/nginx/access.log && \
        ln -sf /dev/stderr /var/log/nginx/error.log

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

echo -e "\033[00;32mnginx has been configured.\033[0m\n";
