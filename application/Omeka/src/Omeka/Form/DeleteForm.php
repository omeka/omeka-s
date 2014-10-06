<?php
namespace Omeka\Form;

use Zend\Form\Form;

class DeleteForm extends Form
{
    public function __construct()
    {
        parent::__construct('delete');

        $this->add(array(
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => array(
                'value' => 'Confirm Delete',
            ),
        ));

        $this->add(array(
            'type' => 'csrf',
            'name' => 'csrf',
        ));
    }
}
