<?php
namespace Omeka\Form;

use Omeka\Form\ResourceValuesCollection;
use Omeka\Api\Representation\Entity\PropertyRepresentation;
use Zend\Form\Fieldset;

class ResourceForm extends AbstractForm
{
    public function buildForm()
    {
        $this->add(array(
            'name'    => 'o:resource_class[o:id]',
            'type'    => 'Select',
            'options' => array(
                'label'         => 'Class',
                'value_options' => $this->getResourceClassPairs(),
                'comment'       => "A type for the resource. Different types have 
                                    different default properties attached to them."
            )
        ));
        
        foreach( $this->getProperties() as $property) {
            $this->addPropertyInputs($property);
        }
    }
    
    public function getAllElements()
    {
        $elements = array();
        $fieldSets = $this->getFieldSets();
        foreach ($fieldSets as $fieldSet) {
            $fieldSetElements = $fieldSet->getElements();
            $elements = array_merge($elements, $fieldSetElements);
        }
        return $elements;
    }

    protected function getResourceClassPairs()
    {
        return $this->options['resource_class_pairs'];
    }

    protected function getProperties()
    {
        return $this->options['properties'];
    }

    /**
     * Boilerplate to add a text property input
     * @param Omeka\Api\Representation\Entity\PropertyRepresentation $property
     */
    protected function addPropertyInputs(PropertyRepresentation $property, $values = array())
    {
        $index = 0;
        $qName = $property->term();
        $fieldset = new Fieldset($qName);
        $fieldset->setOptions(array(
                    'label'   => $property->label(),
                    'comment' => $property->comment(),
                    'term'    => $qName,
                    'data-property-qname' => $qName,
                    'data-property-id'    => $property->id(),
                    'class'               => 'input-value'
                ));

        foreach ($values as $index => $value) {
            $fieldset->add(array(
                'name'       => $qName . "[$index][@value]",
                'type'       => 'Textarea',
                'attributes' => array(
                    'data-property-qname' => $qName,
                    'data-property-id'    => $property->id(),
                    'class'               => 'input-value'
                    ),
                'options'    => array(
                    'label'   => $property->label(),
                    'comment' => $property->comment(),
                    'term'    => $qName,
                    'index'   => $index
                )
            ));
            $index ++;
        }

        $this->add($fieldset);
    }
}

