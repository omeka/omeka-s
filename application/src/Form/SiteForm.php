<?php
namespace Omeka\Form;

use Omeka\Site\Theme\Manager as ThemeManager;
use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;

class SiteForm extends Form
{
    use EventManagerAwareTrait;

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

        $event = new Event('form.add_elements', $this);
        $triggerResult = $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();

        // Separate events because calling $form->getInputFilters()
        // resets everythhing
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
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
