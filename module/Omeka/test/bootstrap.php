<?php
require '../../../bootstrap.php';

// Install a fresh database.
echo 'Dropping database schema...' . PHP_EOL;
\Omeka\Test\DbTestCase::dropSchema();
echo 'Creating database schema...' . PHP_EOL;
\Omeka\Test\DbTestCase::installSchema();
