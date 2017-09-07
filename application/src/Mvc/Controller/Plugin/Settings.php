<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Settings\SettingsInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting settings.
 */
class Settings extends AbstractPlugin
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * Construct the plugin.
     *
     * @param SettingsInterface $settings
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get settings.
     *
     * @return SettingsInterface
     */
    public function __invoke()
    {
        return $this->settings;
    }
}
