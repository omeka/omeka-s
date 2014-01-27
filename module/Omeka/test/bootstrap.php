<?php
require '../../../bootstrap.php';
//make sure error reporting is on for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Install a fresh database.
echo 'Dropping test database schema...' . PHP_EOL;
\Omeka\Test\DbTestCase::dropSchema();
echo 'Creating test database schema...' . PHP_EOL;
\Omeka\Test\DbTestCase::installSchema();
