<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Settings\SettingsInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Settings extends AbstractPlugin
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke()
    {
        return $this->settings;
    }
}
