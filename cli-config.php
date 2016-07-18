<?php
require 'bootstrap.php';

$application = Omeka\Mvc\Application::init(require 'application/config/application.config.php');

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet(
    $application->getServiceManager()->get('Omeka\EntityManager')
);
