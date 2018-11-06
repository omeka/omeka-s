<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\UserSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UserSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new UserSelect($services->get('FormElementManager'));
    }
}
