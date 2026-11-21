#!/bin/sh
set -e

if [ -n "$MYSQL_HOST" ]; then
    {
        echo "user     = \"$MYSQL_USER\""
        echo "password = \"$MYSQL_PASSWORD\""
        echo "dbname   = \"$MYSQL_DATABASE\""
        echo "host     = \"$MYSQL_HOST\""
    } > config/database.ini
fi

chown www-data:www-data files

exec "$@"
