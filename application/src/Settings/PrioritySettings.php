<?php
namespace Omeka\Settings;

use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use Omeka\Settings\UserSettings;

class PrioritySettings
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
     * Can select from the following setting sources: global, site, user.
     *
     * @param string $id The setting ID
     * @param array $sources An array of setting sources in priority order
     * @param mixed $default The default value
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
                        // Not in a site context
                    }
                    break;
                case 'user':
                    try {
                        $setting = $this->userSettings->get($id);
                    } catch (\Exception $e) {
                        // No authenticated user
                    }
                    break;
            }
            if (!(null === $setting || '' === $setting)) {
                return $setting;
            }
        }
        return $default;
    }
}
