<?php

namespace AddTriplestore\Controller\Site;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Http\Client;
use Laminas\Router\RouteStackInterface; // Import RouteStackInterface

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $httpClient = new Client();
        $router = $container->get(RouteStackInterface::class); // Get RouteStackInterface from container
        return new IndexController($router, $httpClient); // Inject it into the controller
    }
}