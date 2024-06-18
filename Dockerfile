FROM php:7.4-apache

RUN a2enmod rewrite headers

RUN apt-get update && \
    docker-php-ext-install mysqli pdo pdo_mysql

# create seetion dir with proper permisstion
RUN mkdir -p /var/cpanel/php/sessions/ea-php72 && \
    chown -R www-data:www-data /var/cpanel/php/sessions/ea-php72
