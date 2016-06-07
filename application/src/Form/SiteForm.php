<?php
namespace Omeka\Form;

use Omeka\Site\Theme\Manager as ThemeManager;
use Zend\Form\Form;

class SiteForm extends Form
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    public function init()
    {
        $this->setAttribute('id', 'site-form');

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'id' => 'title',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => [
                'label' => 'URL slug' // @translate
            ],
            'attributes' => [
                'id' => 'slug',
                'required' => false,
            ],
        ]);
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
