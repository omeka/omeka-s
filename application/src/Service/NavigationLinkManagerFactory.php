<?php
namespace Omeka\Service;

use Omeka\Site\Navigation\Link\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class NavigationLinkManagerFactory implements FactoryInterface
{
    /**
     * Create the navigation link manager service.
     *
     * @return Manager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['navigation_links'])) {
            throw new Exception\ConfigException('Missing navigation link configuration');
        }
        return new Manager($serviceLocator, $config['navigation_links']);
    }
}
