<?php
namespace Omeka\Service;

use Omeka\Thumbnail\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ThumbnailManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $types = array();
        $options = array();

        $config = $serviceLocator->get('Config');
        if (isset($config['thumbnails']['types'])
            && is_array($config['thumbnails']['types'])
        ) {
            $types = $config['thumbnails']['types'];
        }
        if (isset($config['thumbnails']['options'])
            && is_array($config['thumbnails']['options'])
        ) {
            $options = $config['thumbnails']['options'];
        }

        return new Manager($types, $options);
    }
}
