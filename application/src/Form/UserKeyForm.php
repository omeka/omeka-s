<?php
namespace Omeka\Form;

class UserKeyForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'new-key-label',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('New Key Label'),
            ],
            'attributes' => [
                'id' => 'new-key-label',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'new-key-label',
            'required' => false,
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => 255,
                    ],
                ],
            ],
        ]);
    }
}
