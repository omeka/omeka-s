<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class CliFactory implements FactoryInterface
{
    /**
     * Create the CLI service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Cli
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        $config = $serviceLocator->get('Config');

        $strategy = null;
        if (isset($config['cli']['execute_strategy'])) {
            $strategy = $config['cli']['execute_strategy'];
        }

        return new Cli($logger, $strategy);
    }
}
