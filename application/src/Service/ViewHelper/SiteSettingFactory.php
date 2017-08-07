<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Setting;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the site setting view helper.
 */
class SiteSettingFactory implements FactoryInterface
{
    /**
     * Create and return the site setting view helper
     *
     * @return Setting
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Setting($services->get('Omeka\Settings\Site'));
    }
}
