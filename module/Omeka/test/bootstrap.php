<?php
require '../../../bootstrap.php';

// Install a fresh database.
\Omeka\Test\DbTestCase::dropSchema();
\Omeka\Test\DbTestCase::installSchema();
