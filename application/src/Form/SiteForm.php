<?php
namespace Omeka\Form;

class SiteForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        $this->setAttribute('id', 'site-form');

        $this->add(array(
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('URL slug')
            ),
            'attributes' => array(
                'id' => 'slug',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'o:title',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Title'),
            ),
            'attributes' => array(
                'id' => 'title',
                'required' => true,
            ),
        ));
        $themeManager = $this->getServiceLocator()->get('Omeka\Site\ThemeManager');
        $themes = array();
        foreach ($themeManager->getThemes() as $id => $theme) {
            $themes[$id] = $theme->getName();
        }
        $this->add(array(
            'name' => 'o:theme',
            'type' => 'Select',
            'options' => array(
                'label' => $translator->translate('Theme'),
                'value_options' => $themes,
            ),
            'attributes' => array(
                'id' => 'theme',
                'required' => true,
            ),
        ));
    }
}
