<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

ini_set('display_errors', 1);
require_once 'vendor/autoload.php';

$conn = array('driver' => 'pdo_sqlite', 'path' => 'db.sqlite');

$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . '/src'), $isDevMode);
$config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

$em = EntityManager::create($conn, $config);
