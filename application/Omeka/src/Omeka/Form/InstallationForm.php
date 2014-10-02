<?php
namespace Omeka\Form;

use Zend\Form\Form;

class InstallationForm extends Form
{
    public function __construct()
    {
        parent::__construct('installation');

        $this->add(array(
            'name' => 'username',
            'type' => 'Text',
            'options' => array(
                'label' => 'Username',
            ),
            'attributes' => array(
                'id' => 'username',
                'required' => true,
            ),
        ));
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
        $this->add(array(
            'name' => 'name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Name',
            ),
            'attributes' => array(
                'id' => 'name',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
            'options' => array(
                'label' => 'Email',
            ),
            'attributes' => array(
                'id' => 'email',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'email-confirm',
            'type' => 'Email',
            'options' => array(
                'label' => 'Confirm Email',
            ),
            'attributes' => array(
                'id' => 'email-confirm',
                'required' => true,
            ),
        ));

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'username',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Regex',
                    'options' => array(
                        'pattern' => '/^\S+$/', // no whitespace
                    ),
                ),
            ),
        ));
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
        $inputFilter->add(array(
            'name' => 'email-confirm',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Identical',
                    'options' => array(
                        'token' => 'email',
                    ),
                ),
            ),
        ));
    }
}
