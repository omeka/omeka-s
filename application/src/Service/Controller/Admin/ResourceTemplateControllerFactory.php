<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\ResourceTemplateController;
use Zend\ServiceManager\Factory\FactoryInterface;

class ResourceTemplateControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceTemplateController($services->get('Omeka\DataTypeManager'));
    }
}
