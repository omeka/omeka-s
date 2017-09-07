<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Setting;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UserSettingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Setting($services->get('Omeka\Settings\User'));
    }
}
