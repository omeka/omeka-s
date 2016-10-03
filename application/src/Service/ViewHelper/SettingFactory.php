<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Setting;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the setting view helper.
 */
class SettingFactory implements FactoryInterface
{
    /**
     * Create and return the setting view helper
     *
     * @return Setting
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Setting($services->get('Omeka\Settings'));
    }
}
