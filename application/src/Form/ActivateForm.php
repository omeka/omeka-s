<?php
namespace Omeka\Form;

class ActivateForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'password',
            'type' => 'Password',
            'options' => [
                'label' => $translator->translate('Password'),
            ],
            'attributes' => [
                'id' => 'password',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'password-confirm',
            'type' => 'Password',
            'options' => [
                'label' => $translator->translate('Confirm Password'),
            ],
            'attributes' => [
                'id' => 'password-confirm',
                'required' => true,
            ],
        ]);


        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'password',
            'required' => true,
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'min' => 6,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'password-confirm',
            'required' => true,
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'password',
                    ],
                ],
            ],
        ]);
    }
}
