<?php
namespace Omeka\Service\ControllerPlugin;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Omeka\Mvc\Controller\Plugin\GetForm;

class GetFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        $formElementManager = $plugins->getServiceLocator()->get('FormElementManager');
        return new GetForm($formElementManager);
    }
}
