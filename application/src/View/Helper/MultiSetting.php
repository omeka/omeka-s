<?php
namespace Omeka\View\Helper;

use Omeka\Settings\MultiSettings as MultiSettingsService;
use Laminas\View\Helper\AbstractHelper;

class MultiSetting extends AbstractHelper
{
    protected $multiSettings;

    public function __construct(MultiSettingsService $multiSettings)
    {
        $this->multiSettings = $multiSettings;
    }

    public function __invoke($id, array $sources, $default = null)
    {
        return $this->multiSettings->get($id, $sources, $default);
    }
}
