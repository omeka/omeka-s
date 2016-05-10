<?php
namespace Omeka\Form;

class SiteForm extends AbstractForm
{
    public function buildForm()
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
        $themeManager = $this->getServiceLocator()->get('Omeka\Site\ThemeManager');
        $themes = [];
        foreach ($themeManager->getThemes() as $id => $theme) {
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
}
