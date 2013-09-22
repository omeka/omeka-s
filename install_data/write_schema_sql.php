<?php
ini_set('display_errors', 1);

include '../vendor/autoload.php';
include 'SchemaSqlWriter.php';
$writer = new SchemaSqlWriter();
$writer->write();