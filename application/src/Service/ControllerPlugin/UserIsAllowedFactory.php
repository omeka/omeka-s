<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\UserIsAllowed;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserIsAllowedFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new UserIsAllowed($plugins->getServiceLocator()->get('Omeka\Acl'));
    }
}
