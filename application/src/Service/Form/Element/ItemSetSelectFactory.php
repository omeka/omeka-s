<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\ItemSetSelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ItemSetSelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $element = new ItemSetSelect;
        $element->setApiManager($elements->getServiceLocator()->get('Omeka\ApiManager'));
        return $element;
    }
}
