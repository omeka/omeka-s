<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DeleteConfirmSidebar;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeleteConfirmSidebarFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $helpers)
    {
        $formElementManager = $helpers->getServiceLocator()->get('FormElementManager');
        return new DeleteConfirmSidebar($formElementManager);
    }
}
