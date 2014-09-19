<?php
namespace Omeka\View\Helper;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class i18n extends AbstractHelper
{
    /**
     * @var \Zend\View\HelperPluginManager
     */
    protected $viewHelperManager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->viewHelperManager = $serviceLocator->get('ViewHelperManager');
    }

    public function dateFormat($date, $dateType = null, $timeType = null,
        $locale = null, $pattern = null
    ) {
        if (extension_loaded('intl')) {
            $this->viewHelperManager->get('dateFormat')->__invoke(
                $date, $dateType, $timeType, $locale, $pattern
            );
        }
        return $date->format('M j, Y');
    }
}
