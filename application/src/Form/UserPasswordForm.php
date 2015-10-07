<?php
namespace Omeka\Form;

class UserPasswordForm extends AbstractForm
{
    protected $options = [
        'current_password' => false,
    ];

    public function buildForm()
    {
        $translator = $this->getTranslator();

        if ($this->getOption('current_password')){
            $this->add([
                'name' => 'current-password',
                'type' => 'password',
                'options' => [
                    'label' => $translator->translate('Current Password'),
                ],
            ]);
        }

        $this->add([
            'name' => 'password',
            'type' => 'Password',
            'options' => [
                'label' => $translator->translate('New Password'),
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
                'label' => $translator->translate('Confirm New Password'),
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
