<?php
namespace Omeka\Site\Theme;

class Manager
{
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
     * @return Module
     */
    public function registerTheme(Theme $theme)
    {
        $this->themes[$theme->getId()] = $theme;
    }

    /**
     * Check whether the theme INI is valid.
     *
     * @param array $ini
     * @return bool
     */
    public function iniIsValid(array $ini)
    {
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
     * @param string $id
     */
    public function setCurrentTheme($id)
    {
        $this->currentTheme = $this->getTheme($id);
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
