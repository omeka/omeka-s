<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\GetForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GetFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        $formElementManager = $plugins->getServiceLocator()->get('FormElementManager');
        return new GetForm($formElementManager);
    }
}
