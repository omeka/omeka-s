<?php
namespace Omeka\Form;

class SitePageForm extends AbstractForm
{
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
    }
}
