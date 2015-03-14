<?php
namespace Omeka\Service;

use Omeka\Thumbnailer\Thumbnailer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class RdfImporter implements FactoryInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Create the thumbnailer service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Thumbnailer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $strategy = $services->get($config['thumbnails']['strategy']);
        return new Thumbnailer($strategy);
    }
}
