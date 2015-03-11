<?php

namespace Omeka\Service;

use Omeka\FileStore\Local;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the Local file store.
 */
class FileStoreLocalFactory implements FactoryInterface
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
        $fileStore = new Local($localPath, $webPath);
        return $fileStore;
    }
}
