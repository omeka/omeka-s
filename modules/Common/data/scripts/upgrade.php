<?php declare(strict_types=1);

namespace Common;

// Don't use a PsrMessage during install.

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Laminas\Mvc\Controller\Plugin\Url $url
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$url = $plugins->get('url');
$api = $plugins->get('api');
// $config = require dirname(__DIR__, 2) . '/config/module.config.php';
// $settings = $services->get('Omeka\Settings');
// $translate = $plugins->get('translate');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
// $entityManager = $services->get('Omeka\EntityManager');

if (version_compare((string) $oldVersion, '3.4.53', '<')) {
    $this->fixIndexes();
}

if (version_compare((string) $oldVersion, '3.4.57', '<')) {
    $this->fixIndexes();
}

if (version_compare((string) $oldVersion, '3.4.62', '<')) {
    $this->fixIndexes();
}

$this->checkGeneric();
