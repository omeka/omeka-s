<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Settings\MultiSettings as MultiSettingsService;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class MultiSettings extends AbstractPlugin
{
    protected $multiSettings;

    public function __construct(MultiSettingsService $multiSettings)
    {
        $this->multiSettings = $multiSettings;
    }

    public function __invoke()
    {
        return $this->multiSettings;
    }
}
