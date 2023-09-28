<?php
namespace Omeka\Settings;

use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use Omeka\Settings\UserSettings;

class PrioritySetting
{
    protected $settings;
    protected $siteSettings;
    protected $userSettings;

    public function __construct(Settings $settings, SiteSettings $siteSettings, UserSettings $userSettings)
    {
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->userSettings = $userSettings;
    }

    /**
     * Get a setting according to priority.
     *
     * Can select from "global", "site", and/or "user" settings sources.
     *
     * @param string $id
     * @param array $sources An array of setting sources in priority order
     * @param mixed $default
     * @return mixed
     */
    public function get($id, array $sources, $default = null)
    {
        $sources = array_filter(array_unique($sources), function($value) {
            return in_array($value, ['global', 'site', 'user']);
        });
        $setting = null;
        foreach ($sources as $source) {
            switch ($source) {
                case 'global':
                    $setting = $this->settings->get($id);
                    break;
                case 'site':
                    try {
                        $setting = $this->siteSettings->get($id);
                    } catch (\Exception $e) {
                        // Not in a site context.
                    }
                    break;
                case 'user':
                    $setting = $this->userSettings->get($id);
                    break;
            }
            if (!(null === $setting || '' === $setting)) {
                return $setting;
            }
        }
        return $default;
    }
}
