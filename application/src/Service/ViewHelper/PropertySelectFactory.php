<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\PropertySelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PropertySelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $helpers)
    {
        $formElementManager = $helpers->getServiceLocator()->get('FormElementManager');
        return new PropertySelect($formElementManager);
    }
}
