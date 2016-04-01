<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\UserIsAllowed;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the userIsAllowed view helper.
 */
class UserIsAllowedFactory implements FactoryInterface
{
    /**
     * Create and return the userIsAllowed view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return UserIsAllowed
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new UserIsAllowed($serviceLocator->get('Omeka\Acl'));
    }
}
