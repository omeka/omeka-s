<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\ResourceSelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceSelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $element = new ResourceSelect;
        $element->setApiManager($elements->getServiceLocator()->get('Omeka\ApiManager'));
        return $element;
    }
}
