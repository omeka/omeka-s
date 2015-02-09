<?php
/**
 * Perform a job.
 */

use Omeka\Job\Strategy\SynchronousStrategy;
use Omeka\Model\Entity\Job;

require dirname(dirname(__DIR__)) . '/bootstrap.php';

$application = Omeka\Mvc\Application::init(require OMEKA_PATH . '/config/application.config.php');
$serviceLocator = $application->getServiceManager();
$entityManager = $serviceLocator->get('Omeka\EntityManager');
$logger = $serviceLocator->get('Omeka\Logger');

$options = getopt('j:');
if (!isset($options['j'])) {
    $logger->err('No job ID given; use -j <id>');
    exit;
}

$job = $entityManager->find('Omeka\Model\Entity\Job', $options['j']);
if (!$job) {
    $logger->err('There is no job with the given ID');
    exit;
}

// Set the job owner as the authenticated identity.
$owner = $job->getOwner();
if ($owner) {
    $serviceLocator->get('Omeka\AuthenticationService')->getStorage()->write($owner);
}

$job->setPid(getmypid());
$entityManager->flush();

// From here all processing is synchronous.
$serviceLocator->get('Omeka\JobDispatcher')->send($job, new SynchronousStrategy);

$job->setPid(null);
$entityManager->flush();
