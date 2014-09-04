<?php
namespace Omeka\Form;

use Zend\Form\Form;

class ItemForm extends Form
{
    
    public function __construct($resourceClassPairs, $ownerId)
    {
        parent::__construct('item');
        
        $this->add(array(
                'name' => 'o:resource_class[o:id]',
                'type' => 'Select',
                'options' => array(
                    'label' => 'Class',
                    'value_options' => $resourceClassPairs,
                )
        ));

        $this->add(array(
                'name' => 'dcterms:title[0][@value]',
                'type' => 'Text',
                'options' => array(
                    'label' => 'Title'
                )
        ));

        $this->add(array(
                'name' => 'dcterms:title[0][property_id]',
                'type' => 'Hidden',
                'attributes' => array(
                    'value' => '1'
                )
        ));

        $this->add(array(
                'name' => 'dcterms:description[0][@value]',
                'type' => 'Text',
                'options' => array(
                    'label' => 'Description'
                )
        ));

        $this->add(array(
                'name' => 'dcterms:description[0][property_id]',
                'type' => 'Hidden',
                'attributes' => array(
                    'value' => '4'
                )
        ));
        
        $this->add(array(
                'name' => 'o:owner[o:id]',
                'type' => 'Hidden',
                'attributes' => array(
                        'value' => $ownerId
                )
                
        ));
    }
}
