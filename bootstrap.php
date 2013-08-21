<?php
use Omeka\Service\EntityManagerFactory;

ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

$factory = new EntityManagerFactory;
$em = $factory->createEntityManager(include 'config/application.config.php');
