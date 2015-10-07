<?php
namespace Omeka\Form;

class VocabularyForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:label',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Label'),
                'info' => $translator->translate('A human-readable title of the vocabulary.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => [
                'label' => $translator->translate('Comment'),
                'info' => $translator->translate('A human-readable description of the vocabulary.'),
            ],
        ]);
    }
}
