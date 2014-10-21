<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyImportForm extends Form
{
    public function __construct()
    {
        parent::__construct('import-vocabs');

        $this->add(array(
            'name' => 'file',
            'type' => 'file',
            'options' => array(
                'label' => 'Vocabulary File',
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:prefix',
            'type' => 'text',
            'options' => array(
                'label' => 'Prefix',
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => array(
                'label' => 'Namespace URI',
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

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
