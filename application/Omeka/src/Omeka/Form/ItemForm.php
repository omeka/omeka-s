<?php
namespace Omeka\Form;

use Omeka\Form\ResourceValuesCollection;
use Zend\Form\Form;


class ItemForm extends Form
{
    
    public function __construct($name = null, $options = null)
    {
        parent::__construct($name, $options);
        
        $this->add(array(
                'name' => 'o:resource_class[o:id]',
                'type' => 'Select',
                'options' => array(
                    'label' => 'Class',
                    'value_options' => $this->getResourceClassPairs(),
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
                'name' => 'csrf',
                'type' => 'Csrf'
        ));
    }
    
    protected function getResourceClassPairs()
    {
        return $this->options['resource_class_pairs'];
    }
        
    protected function getEntity()
    {
        if (isset($this->options['entity'])) {
            return $this->options['entity'];
        }
        return false;
    }
}
