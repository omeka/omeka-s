<?php
namespace Omeka\Service\Media\FileRenderer;

use Omeka\Media\FileRenderer\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * File renderer manager factory.
 */
class ManagerFactory implements FactoryInterface
{
    /**
     * Create the file renderer manager service.
     *
     * @return Manager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['file_renderers'])) {
            throw new Exception\ConfigException('Missing file renderer configuration');
        }
        return new Manager($serviceLocator, $config['file_renderers']);
    }
}
