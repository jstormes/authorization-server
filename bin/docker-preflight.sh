#!/bin/bash

set -e

SCRIPT=$(readlink -f "$0")

echo "############################################################################################################"
echo "# Starting $SCRIPT"
echo "############################################################################################################"


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
      php /var/www/bin/auth-server.php test-db
fi

echo "############################################################################################################"
echo "# Exit $SCRIPT"
echo "############################################################################################################"
