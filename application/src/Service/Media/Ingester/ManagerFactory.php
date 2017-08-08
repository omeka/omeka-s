<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ManagerFactory implements FactoryInterface
{
    /**
     * Create the media ingester manager service.
     *
     * @return Manager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['media_ingesters'])) {
            throw new Exception\ConfigException('Missing media ingester configuration');
        }
        return new Manager($serviceLocator, $config['media_ingesters']);
    }
}
