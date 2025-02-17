<?php declare(strict_types=1);

namespace DataTypeRdf;

use Common\Stdlib\PsrMessage;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$translate = $plugins->get('translate');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.62')) {
    $message = new \Omeka\Stdlib\Message(
        $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
        'Common', '3.4.62'
    );
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
}

if (version_compare($oldVersion, '3.3.4.3', '<')) {
    $settings->set('datatyperdf_html_mode_resource', $settings->get('datatyperdf_html_mode_resource', $settings->get('blockplus_html_mode')) ?: 'inline');
    $settings->set('datatyperdf_html_config_resource', $settings->get('datatyperdf_html_config_resource', $settings->get('blockplus_html_config')) ?: 'default');

    $message = new PsrMessage(
        'It’s now possible to choose mode of display to edit html values of resources in main params.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.6', '<')) {
    $message = new PsrMessage(
        'Data type xml is now displayed with colors in resource form.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.10', '<')) {
    $message = new PsrMessage(
        'A new data type has been added for json. Warning: the RDF specification is still in {link}discussion{link_end}. In particular, keys of objects should be stored in dictionnary order.', // @translate
        ['link' => '<a href="https://www.w3.org/TR/rdf12-concepts/#section-json" target="_blank" rel="noopener">', 'link_end' => '</a>']
    );
    $message->setEscapeHtml(false);
    $messenger->addSuccess($message);
}
