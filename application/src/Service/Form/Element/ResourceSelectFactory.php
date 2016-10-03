<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\ResourceSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new ResourceSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
