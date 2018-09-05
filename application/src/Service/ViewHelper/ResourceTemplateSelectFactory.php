<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ResourceTemplateSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceTemplateSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceTemplateSelect($services->get('FormElementManager'));
    }
}
