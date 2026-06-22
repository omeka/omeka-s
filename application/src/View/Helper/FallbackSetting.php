<?php
namespace Omeka\View\Helper;

use Omeka\Settings\FallbackSettings as FallbackSettingsService;
use Laminas\View\Helper\AbstractHelper;

class FallbackSetting extends AbstractHelper
{
    protected $fallbackSettings;

    public function __construct(FallbackSettingsService $fallbackSettings)
    {
        $this->fallbackSettings = $fallbackSettings;
    }

    public function __invoke($id, array $sources, $default = null)
    {
        return $this->fallbackSettings->get($id, $sources, $default);
    }
}
