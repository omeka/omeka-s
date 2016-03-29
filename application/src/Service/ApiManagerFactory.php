<?php
namespace Omeka\Service;

use Omeka\Api\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiManagerFactory implements FactoryInterface
{
    /**
     * Create the CLI service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Cli
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapterManager = $serviceLocator->get('Omeka\ApiAdapterManager');
        $acl = $serviceLocator->get('Omeka\Acl');
        $logger = $serviceLocator->get('Omeka\Logger');
        $translator = $serviceLocator->get('MvcTranslator');

        return new Manager($adapterManager, $acl, $logger, $translator);
    }
}
