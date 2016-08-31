<?php
namespace Omeka\Service;

use Omeka\Api\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ApiManagerFactory implements FactoryInterface
{
    /**
     * Create the CLI service.
     *
     * @return Cli
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $adapterManager = $serviceLocator->get('Omeka\ApiAdapterManager');
        $acl = $serviceLocator->get('Omeka\Acl');
        $logger = $serviceLocator->get('Omeka\Logger');
        $translator = $serviceLocator->get('MvcTranslator');

        return new Manager($adapterManager, $acl, $logger, $translator);
    }
}
