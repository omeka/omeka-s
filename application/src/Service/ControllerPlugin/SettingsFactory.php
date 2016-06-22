<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\Settings;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SettingsFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Settings($plugins->getServiceLocator()->get('Omeka\Settings'));
    }
}
