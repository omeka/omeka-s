<?php
require '../../bootstrap.php';

//make sure error reporting is on for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Install a fresh database.
error_log('Dropping test database schema...');
\Omeka\Test\DbTestCase::dropSchema();
error_log('Creating test database schema...');
\Omeka\Test\DbTestCase::installSchema();
