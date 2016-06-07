<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ItemSetSelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ItemSetSelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $helpers)
    {
        $formElementManager = $helpers->getServiceLocator()->get('FormElementManager');
        return new ItemSetSelect($formElementManager);
    }
}
