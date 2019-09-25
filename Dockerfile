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
    && docker-php-ext-install gd zip pdo pdo_mysql

############################################################################
# Configure webserver
############################################################################
RUN a2enmod rewrite

############################################################################
# Add preflights to apache2-foreground, allow hooks into container (Late binding)
############################################################################
RUN sed -i "3i[[ -f /root/bin/docker-preflight.sh ]] && bash /root/bin/docker-preflight.sh" /usr/local/bin/apache2-foreground \
    && sed -i "4i[[ -f /var/www/bin/docker-preflight.sh ]] && bash /var/www/bin/docker-preflight.sh" /usr/local/bin/apache2-foreground

ENV PATH /var/www/vendor/bin:/var/www/bin:/root/bin:root/.composer/vendor/bin:$PATH

WORKDIR /var/www

