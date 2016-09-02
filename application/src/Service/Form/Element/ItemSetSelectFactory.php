<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\ItemSetSelect;
use Zend\ServiceManager\Factory\FactoryInterface;

class ItemSetSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new ItemSetSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
