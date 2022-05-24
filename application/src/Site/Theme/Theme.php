<?php
namespace Omeka\Site\Theme;

class Theme
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var array
     */
    protected $ini;

    /**
     * @var array
     */
    protected $configSpec;

    /**
     * Construct the theme.
     *
     * @param string $id The theme identifier, the directory name
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Get the theme identifier.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the theme state.
     *
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get the theme state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the theme INI data.
     *
     * @param array $ini
     */
    public function setIni($ini)
    {
        $this->ini = $ini;
    }

    /**
     * Get the theme INI data, the entire array or by key.
     *
     * @param string $key
     * @return array|string|null
     */
    public function getIni($key = null)
    {
        if ($key) {
            return $this->ini[$key] ?? null;
        }
        return $this->ini;
    }

    /**
     * Get the name of this theme.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getIni('name') ?: $this->getId();
    }

    /**
     * Set the spec for this theme's configuration form.
     *
     * @param array $configSpec
     */
    public function setConfigSpec($configSpec)
    {
        $this->configSpec = $configSpec;
    }

    /**
     * Get the spec for this theme's configuration form.
     */
    public function getConfigSpec()
    {
        return $this->configSpec;
    }

    public function getSettingsKey()
    {
        return "theme_settings_" . $this->getId();
    }

    public function getThumbnail($key = null)
    {
        if ($key) {
            return '/themes/' . $key . "/theme.jpg";
        }
        return '/themes/' . $this->id . "/theme.jpg";
    }

    /**
     * Return whether this theme is user-configurable.
     *
     * A configurable theme needs a [config] section in its INI file and at
     * least one element defined there.
     *
     * @return bool
     */
    public function isConfigurable()
    {
        $configSpec = $this->getConfigSpec();
        return $configSpec && $configSpec['elements'];
    }

    /**
     * Return whether this theme has resource page blocks configuration.
     *
     * @return bool
     */
    public function isConfigurableResourcePageBlocks()
    {
        $configSpec = $this->getConfigSpec();
        return $configSpec && isset($configSpec['resource_page_blocks']);
    }

    /**
     * Return local path to or within this theme.
     *
     * With no arguments, this is the path to the theme's top-level directory.
     * Arguments are additional path segments to append, and will be joined by
     * a slash.
     */
    public function getPath(string ...$subsegments): string
    {
        $segments = array_merge([OMEKA_PATH, 'themes', $this->id], $subsegments);
        return implode('/', $segments);
    }
}
