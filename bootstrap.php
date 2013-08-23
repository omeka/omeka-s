<?php
use Omeka\Service\EntityManagerFactory;

ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

$factory = new EntityManagerFactory;
$config = include 'config/application.config.php';
$conn = $config['entity_manager'];
$em = $factory->createEntityManager($conn);
