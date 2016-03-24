<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\I18n;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the i18n view helper.
 */
class I18nFactory implements FactoryInterface
{
    /**
     * Create and return the i18n view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return I18n
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        $timezone = $serviceLocator->get('Omeka\Settings')->get('time_zone', 'UTC');
        $dateFormatHelper = null;
        if (extension_loaded('intl')) {
            $dateFormatHelper = $viewServiceLocator->get('DateFormat');
        }
        return new I18n($timezone, $dateFormatHelper);
    }
}
