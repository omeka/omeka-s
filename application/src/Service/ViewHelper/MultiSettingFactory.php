<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\MultiSetting;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MultiSettingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new MultiSetting($services->get('Omeka\Settings\Multi'));
    }
}
