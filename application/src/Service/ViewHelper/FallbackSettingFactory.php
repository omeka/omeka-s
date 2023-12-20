<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\FallbackSetting;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class FallbackSettingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new FallbackSetting($services->get('Omeka\Settings\Fallback'));
    }
}
