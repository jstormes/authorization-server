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
      if ! php /var/www/bin/auth-server.php verify-db; then
        echo "DB validation failed.  Creating Database."
        php /var/www/bin/auth-server.php create-db
        doctrine --force orm:schema-tool:update
        php /var/www/bin/auth-server.php create-history-table user
      fi
fi

echo "############################################################################################################"
echo "# Exit $SCRIPT"
echo "############################################################################################################"
