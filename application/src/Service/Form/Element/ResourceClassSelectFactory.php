<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\ResourceClassSelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceClassSelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $element = new ResourceClassSelect;
        $element->setApiManager($elements->getServiceLocator()->get('Omeka\ApiManager'));
        return $element;
    }
}
