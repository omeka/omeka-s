<?php
namespace Omeka\Form;

use Omeka\Form\ResourceValuesCollection;
use Omeka\Api\Representation\Entity\PropertyRepresentation;
use Zend\Form\Fieldset;

class ItemForm extends AbstractForm
{
    public function buildForm()
    {
        $this->add(array(
            'name'    => 'o:resource_class[o:id]',
            'type'    => 'Select',
            'options' => array(
                'label'         => 'Class',
                'value_options' => $this->getResourceClassPairs(),
                'comment'       => "A type for the item. Different types have 
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
        //question: add elements/values here in the form, or in the propertyInputs helper?
        //putting in here might mean using fieldsets, and making the helper take the entire
        //fieldset to display
        
        //plus, should this use/add something analogous 
        //to Omeka\Api\Representation\EntityAbstractResourceEntityRepresentation::displayValues()???
        
        //key question is where the indexes get added to the input names
        
        //radical move is to extend Fieldset to PropertySet and build helpers/partials off of that
        //would let me stuff needed options like values into PropertySet::options for use in the helper
        
        //
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
        //always add an empty at the end
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
        $this->add($fieldset);
    }
}

