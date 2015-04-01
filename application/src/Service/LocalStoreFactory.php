<?php
namespace Omeka\Service;

use Omeka\File\Store\LocalStore;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the Local file store.
 */
class LocalStoreFactory implements FactoryInterface
{
    /**
     * Create and return the Local file store
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Local
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $viewHelpers = $serviceLocator->get('ViewHelperManager');
        $serverUrl = $viewHelpers->get('ServerUrl');
        $basePath = $viewHelpers->get('BasePath');

        $localPath = OMEKA_PATH . '/files';
        $webPath = $serverUrl($basePath('files'));
        $fileStore = new LocalStore($localPath, $webPath);
        return $fileStore;
    }
}
