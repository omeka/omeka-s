<?php
namespace Omeka\Form;

class UserKeyForm extends AbstractForm
{
    public function buildForm()
    {
        $this->add([
            'name' => 'new-key-label',
            'type' => 'Text',
            'options' => [
                'label' => 'New Key Label', // @translate
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
