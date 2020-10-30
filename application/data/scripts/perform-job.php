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

$options = getopt(null, ['job-id:', 'base-path:', 'server-url:']);
if (!isset($options['job-id'])) {
    $logger->err('No job ID given; use --job-id <id>');
    exit;
}
if (!isset($options['base-path'])) {
    $logger->err('No base path given; use --base-path <basePath>');
    exit;
}
if (!isset($options['server-url'])) {
    $logger->err('No server URL given; use --server-url <serverUrl>');
    exit;
}

$job = $entityManager->find(Job::class, $options['job-id']);
if (!$job) {
    $logger->err('There is no job with the given ID');
    exit;
}

$viewHelperManager = $serviceLocator->get('ViewHelperManager');
$viewHelperManager->get('BasePath')->setBasePath($options['base-path']);
$serviceLocator->get('Router')->setBaseUrl($options['base-path']);

$serverUrlParts = parse_url($options['server-url']);
$scheme = $serverUrlParts['scheme'];
$host = $serverUrlParts['host'];
if (isset($serverUrlParts['port'])) {
    $port = $serverUrlParts['port'];
} elseif ($serverUrlParts['scheme'] === 'http') {
    $port = 80;
} elseif ($serverUrlParts['scheme'] === 'https') {
    $port = 443;
} else {
    $port = null;
}
$serverUrlHelper = $viewHelperManager->get('ServerUrl');
$serverUrlHelper->setPort($port);
$serverUrlHelper->setScheme($scheme);
$serverUrlHelper->setHost($host);

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
