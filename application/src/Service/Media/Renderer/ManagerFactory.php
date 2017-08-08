<?php
namespace Omeka\Service\Media\Renderer;

use Omeka\Media\Renderer\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ManagerFactory implements FactoryInterface
{
    /**
     * Create the media renderer manager service.
     *
     * @return Manager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['media_renderers'])) {
            throw new Exception\ConfigException('Missing media renderer configuration');
        }
        return new Manager($serviceLocator, $config['media_renderers']);
    }
}
