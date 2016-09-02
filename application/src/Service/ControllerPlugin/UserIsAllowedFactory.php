<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\UserIsAllowed;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserIsAllowedFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new UserIsAllowed($services->get('Omeka\Acl'));
    }
}
