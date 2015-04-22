<?php
namespace Omeka\Form;

class SiteForm extends AbstractForm
{
    protected $options = array('include_role' => false);

    public function buildForm()
    {
        $translator = $this->getTranslator();

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
        $this->add(array(
            'name' => 'o:theme',
            'type' => 'Select',
            'options' => array(
                'label' => $translator->translate('Theme'),
                'value_options' => array(
                    'default' => $translator->translate('Default')
                )
            ),
            'attributes' => array(
                'id' => 'theme',
                'required' => true,
            ),
        ));
    }
}
