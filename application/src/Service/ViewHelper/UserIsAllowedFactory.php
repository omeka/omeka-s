<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\UserIsAllowed;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the userIsAllowed view helper.
 */
class UserIsAllowedFactory implements FactoryInterface
{
    /**
     * Create and return the userIsAllowed view helper
     *
     * @return UserIsAllowed
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new UserIsAllowed($services->get('Omeka\Acl'));
    }
}
