<?php
namespace Omeka\Site\Theme;

class Manager
{
    const STATE_ACTIVE = 'active';
    const STATE_INVALID_INI = 'invalid_ini';
    const STATE_INVALID_OMEKA_VERSION = 'invalid_omeka_version';
    const STATE_NOT_FOUND = 'not_found';

    /**
     * @var array Registered themes
     */
    protected $themes = [];

    /**
     * @var Theme|null The current theme
     */
    protected $currentTheme;

    /**
     * Register a new theme.
     *
     * @param string $id
     * @return Theme
     */
    public function registerTheme($id)
    {
        $theme = new Theme($id);
        $this->themes[$id] = $theme;
        return $theme;
    }

    /**
     * Check whether the theme INI is valid.
     *
     * @param Theme $theme
     * @return bool
     */
    public function iniIsValid(Theme $theme)
    {
        $ini = $theme->getIni();
        if (!isset($ini['name'])) {
            return false;
        }
        return true;
    }

    /**
     * Check whether a theme is registered.
     *
     * @param string $id
     * @return bool
     */
    public function isRegistered($id)
    {
        return array_key_exists($id, $this->themes);
    }

    /**
     * Get a registered theme.
     *
     * @param string $id
     * @return Theme|false Returns false when id is invalid
     */
    public function getTheme($id)
    {
        return $this->isRegistered($id) ? $this->themes[$id] : false;
    }

    /**
     * Get all registered themes.
     *
     * @return array
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * Set the current theme.
     *
     * @param Theme $theme
     */
    public function setCurrentTheme(Theme $theme)
    {
        $this->currentTheme = $theme;
    }

    /**
     * Get the current theme.
     *
     * @return Theme|null
     */
    public function getCurrentTheme()
    {
        return $this->currentTheme;
    }
}
