<?php
namespace Omeka\Form;

use Omeka\Site\Theme\Manager as ThemeManager;
use Zend\Form\Form;

class SiteThemeForm extends Form
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    public function init()
    {
        $this->setAttribute('id', 'site-theme-form');

        $themes = [];
        foreach ($this->getThemeManager()->getThemes() as $id => $theme) {
            $themes[$id] = $theme->getName();
        }
        $this->add([
            'name' => 'o:theme',
            'type' => 'Select',
            'options' => [
                'label' => 'Theme', // @translate
                'value_options' => $themes,
            ],
            'attributes' => [
                'id' => 'theme',
                'required' => true,
            ],
        ]);
    }

    /**
     * @param ThemeManager $themeManager
     */
    public function setThemeManager(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @return ThemeManager
     */
    public function getThemeManager()
    {
        return $this->themeManager;
    }
}