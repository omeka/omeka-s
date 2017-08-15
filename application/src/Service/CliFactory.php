<?php
namespace Omeka\Service;

use Omeka\Stdlib\Cli;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class CliFactory implements FactoryInterface
{
    /**
     * Create the CLI service.
     *
     * @return Cli
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
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
