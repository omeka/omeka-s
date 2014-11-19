<?php
namespace Omeka\Form;

class VocabularyForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'o:label',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Label'),
                'info' => $translator->translate('A human-readable title of the vocabulary.'),
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
                'info' => $translator->translate('A human-readable description of the vocabulary.'),
            ),
        ));

        $this->add(array(
            'type' => 'csrf',
            'name' => 'csrf',
        ));
    }
}
