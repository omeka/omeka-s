<?php
namespace Omeka\Service;

use Omeka\File\Manager as FileManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config')['file_manager'];
        return new FileManager($config);
    }
}
