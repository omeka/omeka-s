<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\ResourceTemplateSelect;
use Zend\ServiceManager\Factory\FactoryInterface;

class ResourceTemplateSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new ResourceTemplateSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
