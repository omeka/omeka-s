<?php
namespace GraphDBDataSync\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Http\Client;
use Laminas\Mvc\Controller\PluginManager;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Get all required services
        $acl = $container->get('Omeka\Acl');
        $httpClient = $container->get(Client::class);
        $formElementManager = $container->get('FormElementManager');
        
        // Get ControllerPluginManager and its plugins
        $controllerPluginManager = $container->get('ControllerPluginManager');
        $messenger = $controllerPluginManager->get('messenger');
        $urlHelper = $controllerPluginManager->get('url');

        return new IndexController(
            $acl,
            $messenger,
            $httpClient,
            $controllerPluginManager,
            $formElementManager,
            $urlHelper
        );
    }
}