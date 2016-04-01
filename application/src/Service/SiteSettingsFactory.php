<?php
namespace Omeka\Service;

use Omeka\Settings\SiteSettings as SiteSettings;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteSettingsFactory implements FactoryInterface
{
    /**
     * Create the site settings service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return SiteSettings
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $status = $serviceLocator->get('Omeka\Status');
        return new SiteSettings($connection, $status);
    }
}
