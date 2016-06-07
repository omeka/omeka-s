<?php
namespace Omeka\Form;

use Zend\Form\Form;

class UserPasswordForm extends Form
{
    protected $options = [
        'current_password' => false,
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        if ($this->getOption('current_password')){
            $this->add([
                'name' => 'current-password',
                'type' => 'password',
                'options' => [
                    'label' => 'Current Password', // @translate
                ],
            ]);
        }

        $this->add([
            'name' => 'password',
            'type' => 'Password',
            'options' => [
                'label' => 'New Password', // @translate
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
                'label' => 'Confirm New Password', // @translate
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
