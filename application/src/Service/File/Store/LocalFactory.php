<?php
namespace Omeka\Service\File\Store;

use Omeka\File\Store\Local;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the Local file store.
 */
class LocalFactory implements FactoryInterface
{
    /**
     * Create and return the Local file store
     *
     * @return Local
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        $viewHelpers = $serviceLocator->get('ViewHelperManager');
        $serverUrl = $viewHelpers->get('ServerUrl');
        $basePath = $viewHelpers->get('BasePath');

        $localPath = OMEKA_PATH . '/files';
        $webPath = $serverUrl($basePath('files'));
        $fileStore = new Local($localPath, $webPath, $logger);
        return $fileStore;
    }
}
