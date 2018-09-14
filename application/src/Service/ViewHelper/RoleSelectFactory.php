<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\RoleSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class RoleSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new RoleSelect($services->get('FormElementManager'));
    }
}
