<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DeleteConfirm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeleteConfirmFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $helpers)
    {
        $formElementManager = $helpers->getServiceLocator()->get('FormElementManager');
        return new DeleteConfirm($formElementManager);
    }
}
