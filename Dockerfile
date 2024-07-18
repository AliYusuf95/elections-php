FROM php:7.4-apache

RUN a2enmod rewrite headers

RUN apt-get update && \
    apt-get install -y \
        libzip-dev \
        zip \
        &&\
    pecl channel-update pecl.php.net && pecl install xdebug-3.1.6 &&\
    docker-php-ext-install mysqli pdo pdo_mysql zip &&\
    docker-php-ext-enable xdebug


RUN echo 'zend_extension=xdebug"' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.mode=debug,develop' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo 'xdebug.var_display_max_data=-1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo 'xdebug.var_display_max_children=-1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo 'xdebug.var_display_max_depth=-1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# create seetion dir with proper permisstion
RUN mkdir -p /var/cpanel/php/sessions/ea-php72 && \
    chown -R www-data:www-data /var/cpanel/php/sessions/ea-php72
