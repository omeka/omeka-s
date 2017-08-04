<?php
namespace Omeka\Service\Settings;

use Omeka\Settings\SiteSettings;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SiteSettingsFactory implements FactoryInterface
{
    /**
     * Create the site settings service.
     *
     * @return SiteSettings
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SiteSettings(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\Status')
        );
    }
}
