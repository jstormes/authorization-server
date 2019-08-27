#!/bin/bash

set -e

echo "Starting preflight"

############################################################################
# Set permission on data directory, for Zend Config Cache.
############################################################################
chgrp -R www-data /var/www/data
chmod -R g+rw /var/www/data
