<?php
namespace Omeka\Form;

use Zend\Form\Form;

class UserPasswordForm extends Form
{
    public function __construct()
    {
        parent::__construct('user-password');
        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
            'options' => array(
                'label' => 'Password',
            ),
            'attributes' => array(
                'id' => 'password',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'password-confirm',
            'type' => 'Password',
            'options' => array(
                'label' => 'Confirm Password',
            ),
            'attributes' => array(
                'id' => 'password-confirm',
                'required' => true,
            ),
        ));
        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'password',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'min' => 6,
                    ),
                ),
            ),
        ));
        $inputFilter->add(array(
            'name' => 'password-confirm',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Identical',
                    'options' => array(
                        'token' => 'password',
                    ),
                ),
            ),
        ));
    }
}
