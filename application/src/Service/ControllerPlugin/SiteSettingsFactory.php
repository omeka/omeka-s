<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\SiteSettings;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteSettingsFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new SiteSettings($plugins->getServiceLocator()->get('Omeka\SiteSettings'));
    }
}
