<?php
namespace Omeka\Form;

use Zend\Form\Form;

class UserForm extends Form
{
    public function __construct()
    {
        parent::__construct('user');

        $this->add(array(
            'name' => 'o:username',
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
            'name' => 'o:name',
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
            'name' => 'o:email',
            'type' => 'Email',
            'options' => array(
                'label' => 'Email',
            ),
            'attributes' => array(
                'id' => 'email',
                'required' => true,
            ),
        ));
        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'o:username',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'min' => 6,
                    ),
                ),
                array(
                    'name' => 'Regex',
                    'options' => array(
                        'pattern' => '/^\S+$/', // no whitespace
                    ),
                ),
            ),
        ));
    }
}
