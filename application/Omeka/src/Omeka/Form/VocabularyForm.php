<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyForm extends Form
{
    public function __construct()
    {
        parent::__construct('vocabulary');

        $this->add(array(
            'name' => 'o:label',
            'type' => 'text',
            'options' => array(
                'label' => 'Label',
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Comment',
            ),
        ));
    }
}
