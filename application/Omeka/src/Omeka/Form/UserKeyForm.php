<?php
namespace Omeka\Form;

use Zend\Form\Form;

class UserKeyForm extends Form
{
    public function __construct()
    {
        parent::__construct('user-key');
        $this->add(array(
            'name' => 'new-key-label',
            'type' => 'Text',
            'options' => array(
                'label' => 'New Key Label',
            ),
            'attributes' => array(
                'id' => 'new-key-label',
            ),
        ));
        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'new-key-label',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'max' => 255,
                    ),
                ),
            ),
        ));
    }
}
