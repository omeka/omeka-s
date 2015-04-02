<?php
namespace Omeka\Service;

use Omeka\File\Manager as FileManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (isset($config['file_manager']) && is_array($config['file_manager'])) {
            $config = $config['file_manager'];
        } else {
            $config = array();
        }
        return new FileManager($config);
    }
}
