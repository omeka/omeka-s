<?php
namespace Omeka\View\Helper;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper to get settings from the settings service.
 */
class Setting extends AbstractHelper
{
    /**
     * @var \Omeka\Service\Settings
     */
    protected $settings;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->settings = $serviceLocator->get('Omeka\Settings');
    }

    /**
     * Get a setting
     *
     * Will return null if no setting exists with the passed ID.
     *
     * @param string $id
     * @return mixed
     */
    public function __invoke($id, $default = null)
    {
        return $this->settings->get($id, $default);
    }
}
