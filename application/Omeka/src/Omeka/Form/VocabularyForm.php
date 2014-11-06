<?php
namespace Omeka\Form;

class VocabularyForm extends AbstractForm
{
    public function getFormName()
    {
        return 'vocabulary';
    }

    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'o:label',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Label'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => array(
                'label' => $translator->translate('Comment'),
            ),
        ));
    }
}
