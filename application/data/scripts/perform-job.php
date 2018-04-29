<?php
/**
 * Perform a job.
 */
use Omeka\Entity\Job;

require dirname(dirname(dirname(__DIR__))) . '/bootstrap.php';

$application = Omeka\Mvc\Application::init(require OMEKA_PATH . '/application/config/application.config.php');
$serviceLocator = $application->getServiceManager();
$entityManager = $serviceLocator->get('Omeka\EntityManager');
$logger = $serviceLocator->get('Omeka\Logger');

$options = getopt(null, ['job-id:', 'base-path:']);
if (!isset($options['job-id'])) {
    $logger->err('No job ID given; use --job-id <id>');
    exit;
}
if (!isset($options['base-path'])) {
    $logger->err('No base path given; use --base-path <basePath>');
    exit;
}

$job = $entityManager->find(Job::class, $options['job-id']);
if (!$job) {
    $logger->err('There is no job with the given ID');
    exit;
}

$serviceLocator->get('ViewHelperManager')->get('BasePath')->setBasePath($options['base-path']);

// Set the job owner as the authenticated identity.
$owner = $job->getOwner();
if ($owner) {
    $serviceLocator->get('Omeka\AuthenticationService')->getStorage()->write($owner);
}

$job->setPid(getmypid());
$entityManager->flush();

// From here all processing is synchronous.
$strategy = $serviceLocator->get('Omeka\Job\DispatchStrategy\Synchronous');
$serviceLocator->get('Omeka\Job\Dispatcher')->send($job, $strategy);

$job->setPid(null);
$entityManager->flush();
