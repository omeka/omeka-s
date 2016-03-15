<?php
namespace Omeka\Form;

class SiteForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        $this->setAttribute('id', 'site-form');

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Title'),
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
                'label' => $translator->translate('URL slug')
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
                'label' => $translator->translate('Theme'),
                'value_options' => $themes,
            ],
            'attributes' => [
                'id' => 'theme',
                'required' => true,
            ],
        ]);
    }
}
