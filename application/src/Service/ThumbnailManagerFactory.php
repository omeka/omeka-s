<?php
namespace Omeka\Service;

use Omeka\Thumbnail\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ThumbnailManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $manager = new Manager($config);

        $config = $serviceLocator->get('Config')['thumbnails'];
        if (isset($config['types']) && is_array($config['types'])) {
            foreach ($config['types'] as $type => $constraint) {
                $manager->setType($type, $constraint);
            }
        }
        if (isset($config['square_types']) && is_array($config['square_types'])) {
            foreach ($config['square_types'] as $type => $constraint) {
                $manager->setSquareType($type, $constraint);
            }
        }

        return $manager;
    }
}
