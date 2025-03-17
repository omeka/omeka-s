<?php
namespace Collecting\Service\Controller\Site;

use Collecting\Controller\Site\IndexController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IndexController(
            $services->get('Omeka\Acl'),
            $services->get('Collecting\MediaTypeManager')
        );
    }
}
