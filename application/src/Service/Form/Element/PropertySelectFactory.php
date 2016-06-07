<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PropertySelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $element = new PropertySelect;
        $element->setApiManager($elements->getServiceLocator()->get('Omeka\ApiManager'));
        return $element;
    }
}
