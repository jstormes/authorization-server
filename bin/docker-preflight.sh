#!/bin/bash

set -e

echo "Starting preflight"

############################################################################
# Set permission on data directory, for Zend Config Cache.
############################################################################
chgrp -R www-data /var/www/data
chmod -R g+rw /var/www/data

apt-get update -y 1>/dev/null 2>/dev/null

############################################################################
# Install composer if not found
############################################################################
if ! which composer 1>/dev/null 2>/dev/null
then
    apt-get install -y wget git
    wget https://getcomposer.org/installer
    php installer
    rm installer
    mv composer.phar /usr/local/bin/composer
    chmod u+x /usr/local/bin/composer
fi

############################################################################
# Setup XDebug, always try and start XDebug connection to requesting ip
# DO NOT DO THIS ON PUBLICLY ACCESSIBLE SYSTEMS!!!!!!!
############################################################################
if ! find /usr/local/lib/php/extensions/ -name xdebug.so 1>/dev/null 2>/dev/null
then
    yes | pecl install xdebug
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_autostart=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_connect_back=on"  >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.idekey=default-docker" >> /usr/local/etc/php/conf.d/xdebug.ini
fi

############################################################################
# Add host.docker.internal if not found
############################################################################
apt-get install -y inetutils-ping iproute2 1>/dev/null 2>/dev/null
if ! ping -c1 host.docker.internal 1>/dev/null 2>/dev/null
then
    ip -4 route list match 0/0 | awk '{print $3 " host.docker.internal"}' >> /etc/hosts
fi

composer install
composer development-enable