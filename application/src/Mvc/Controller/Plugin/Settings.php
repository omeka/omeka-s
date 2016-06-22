<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Settings\Settings as SettingsService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Settings extends AbstractPlugin
{
    /**
     * @var SettingsService
     */
    protected $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke()
    {
        return $this->settings;
    }
}
