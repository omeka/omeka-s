<?php
namespace Omeka\Service;

use Omeka\Settings\SiteSettings as SiteSettings;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SiteSettingsFactory implements FactoryInterface
{
    /**
     * Create the site settings service.
     *
     * @return SiteSettings
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $status = $serviceLocator->get('Omeka\Status');
        return new SiteSettings($connection, $status);
    }
}
