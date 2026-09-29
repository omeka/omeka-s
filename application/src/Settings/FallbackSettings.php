<?php
namespace Omeka\Settings;

class FallbackSettings
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
     * Get a setting prioritized by source.
     *
     * Can select from the following sources: global, site, user.
     *
     * @param string $id The setting ID
     * @param array $sources An array of setting sources in fallback order
     * @param mixed $default The default value
     * @return mixed
     */
    public function get($id, array $sources, $default = null)
    {
        return $this->getWithSource($id, $sources, $default)['value'];
    }

    /**
     * Get a setting prioritized by source, and the source it came from.
     *
     * Works like get(), but also reports which source supplied the value. The
     * source is null when no source had a value and the default was used. Site
     * and user settings can be read for an explicit target, for example to
     * read a site's settings outside a site context.
     *
     * @param string $id The setting ID
     * @param array $sources An array of setting sources in fallback order
     * @param mixed $default The default value
     * @param array $targetIds Target IDs keyed by source ("site", "user")
     * @return array ['value' => mixed, 'source' => string|null]
     */
    public function getWithSource($id, array $sources, $default = null, array $targetIds = [])
    {
        $setting = null;
        foreach (array_unique($sources) as $source) {
            switch ($source) {
                case 'global':
                    $setting = $this->settings->get($id);
                    break;
                case 'site':
                    try {
                        $setting = $this->siteSettings->get($id, null, $targetIds['site'] ?? null);
                    } catch (\Exception $e) {
                        // Not in a site context
                    }
                    break;
                case 'user':
                    try {
                        $setting = $this->userSettings->get($id, null, $targetIds['user'] ?? null);
                    } catch (\Exception $e) {
                        // No authenticated user
                    }
                    break;
            }
            if (!(null === $setting || '' === $setting)) {
                return ['value' => $setting, 'source' => $source];
            }
        }
        return ['value' => $default, 'source' => null];
    }
}
