FROM php:7-apache

COPY . /var/www
COPY ./public /var/www/html

############################################################################
# Install requried libraries, should be the same across dev, QA, etc...
############################################################################
RUN apt-get -y update \
    && apt-get install -y curl zip unzip libpng-dev libzip-dev \
    && apt-get -y autoremove \
    && apt-get -y clean \
    && yes '' | pecl install -f redis \
       && rm -rf /tmp/pear \
       && docker-php-ext-enable redis \
    && docker-php-ext-install gd zip

############################################################################
# Configure webserver
############################################################################
RUN a2enmod rewrite 

