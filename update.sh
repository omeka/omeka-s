#!/usr/bin/env bash

echo '- Updating from origin'
git pull

echo
echo '- Install dependencies'
gulp deps
echo

echo '- Compare changes in configuration'
diff config/local.config.php config/local.config.php.dist
echo

echo '- Go to http://OMEKA_BASE_URL/install to run database migrations'
echo
