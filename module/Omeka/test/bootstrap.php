<?php
require '../../../bootstrap.php';

// Install a fresh database.
echo 'Dropping test database schema...' . PHP_EOL;
\Omeka\Test\DbTestCase::dropSchema();
echo 'Creating test database schema...' . PHP_EOL;
\Omeka\Test\DbTestCase::installSchema();
