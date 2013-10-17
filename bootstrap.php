<?php
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

$factory = new \Omeka\Service\EntityManagerFactory;
$config = include 'config/local.config.php';
$em = $factory->createEntityManager($config['entity_manager']);
