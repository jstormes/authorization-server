#!/bin/bash

set -e

echo "Starting docker-preflight"

############################################################################
# Set permission on data directory, for Zend Config Cache.
############################################################################
chgrp -R www-data /var/www/data
chmod -R g+rw /var/www/data
chmod a+x /var/www/bin

if [[ -z "DB" ]]
then
      echo "DB environment variable is empty."
else
      echo "DB environment variable is NOT empty, testing DB."
      php /var/www/bin/test-db.php
fi

echo "Ending docker-preflight"
