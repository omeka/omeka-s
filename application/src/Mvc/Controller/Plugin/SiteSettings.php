<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Settings\SiteSettings as SiteSettingsService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class SiteSettings extends AbstractPlugin
{
    /**
     * @var SiteSettingsService
     */
    protected $siteSettings;

    public function __construct(SiteSettingsService $siteSettings)
    {
        $this->siteSettings = $siteSettings;
    }

    public function __invoke()
    {
        return $this->siteSettings;
    }
}
