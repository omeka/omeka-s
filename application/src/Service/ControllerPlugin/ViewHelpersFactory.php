<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\ViewHelpers;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ViewHelpersFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new ViewHelpers($plugins->getServiceLocator()->get('ViewHelperManager'));
    }
}
