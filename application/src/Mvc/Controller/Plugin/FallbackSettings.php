<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Settings\FallbackSettings as FallbackSettingsService;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class FallbackSettings extends AbstractPlugin
{
    protected $fallbackSettings;

    public function __construct(FallbackSettingsService $fallbackSettings)
    {
        $this->fallbackSettings = $fallbackSettings;
    }

    public function __invoke()
    {
        return $this->fallbackSettings;
    }
}
