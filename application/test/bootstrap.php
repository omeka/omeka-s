<?php
require dirname(dirname(__DIR__)) . '/bootstrap.php';

//make sure error reporting is on for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Install a fresh database.
file_put_contents('php://stdout', "Dropping test database schema…\n");
\Omeka\Test\DbTestCase::dropSchema();
file_put_contents('php://stdout', "Creating test database schema…\n");
\Omeka\Test\DbTestCase::installSchema();
