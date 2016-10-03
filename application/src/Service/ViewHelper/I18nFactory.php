<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\I18n;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the i18n view helper.
 */
class I18nFactory implements FactoryInterface
{
    /**
     * Create and return the i18n view helper
     *
     * @return I18n
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $viewHelperManager = $services->get('ViewHelperManager');
        $timezone = $services->get('Omeka\Settings')->get('time_zone', 'UTC');
        $dateFormatHelper = null;
        if (extension_loaded('intl')) {
            $dateFormatHelper = $viewHelperManager->get('DateFormat');
        }
        return new I18n($timezone, $dateFormatHelper);
    }
}
