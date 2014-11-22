<?php
namespace Omeka\Form;

use Omeka\Form\ResourceValuesCollection;
use Omeka\Api\Representation\Entity\PropertyRepresentation;

class ItemForm extends AbstractForm
{
    public function buildForm()
    {
        $this->add(array(
            'name'    => 'o:resource_class[o:id]',
            'type'    => 'Select',
            'options' => array(
                'label' => 'Class',
                'value_options' => $this->getResourceClassPairs(),
            )
        ));

        foreach( $this->getProperties() as $property) {
            $this->addPropertyInputs($property);
        }

        $this->add(array(
            'name' => 'csrf',
            'type' => 'Csrf'
        ));
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
    protected function addPropertyInputs(PropertyRepresentation $property)
    {
        $qName = $property->term();
        $this->add(array(
            'name'       => $qName . "[0][@value]",
            'type'       => 'Textarea',
            'attributes' => array(
                'data-property-qname' => $qName,
                'data-property-id'    => $property->id(),
                'class'               => 'input-value'
                ),
            'options'    => array(
                'label' => $property->label()
            )
        ));

        $this->add(array(
            'name'       => $qName . "[0][property_id]",
            'type'       => 'Hidden',
            'attributes' => array(
                'value'               => $property->id(),
                'data-property-qname' => $qName,
                'class'               => 'input-id'
            )
        ));
    }
}

