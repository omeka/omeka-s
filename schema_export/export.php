<?php
ini_set('display_errors', 1);

include '../vendor/autoload.php';
include 'SchemaExporter.php';
$exporter = new Omeka\Install\SchemaExporter();
$exporter->export();