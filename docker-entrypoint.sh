#!/bin/bash

cp config/database.ini.dist config/database.ini

sed -i -e 's/user     = ""/user     = "'$MYSQL_USER'"/g' config/database.ini

sed -i -e 's/password = ""/password = "'$MYSQL_PASSWORD'"/g' config/database.ini

sed -i -e 's/dbname   = ""/dbname   = "'$MYSQL_DATABASE'"/g' config/database.ini

sed -i -e 's/host     = ""/host     = "'$MYSQL_HOST'"/g' config/database.ini

exec "$@"
