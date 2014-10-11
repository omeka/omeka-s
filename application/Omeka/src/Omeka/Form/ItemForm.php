<?php
namespace Omeka\Form;

use Omeka\Form\ResourceValuesCollection;
use Omeka\Api\Representation\Entity\PropertyRepresentation;
use Zend\Form\Form;

class ItemForm extends Form
{
    public function __construct($name = null, $options = null)
    {
        parent::__construct($name, $options);
        $this->add(array(
            'name'    => 'o:resource_class[o:id]',
            'type'    => 'Select',
            'options' => array(
                'label' => 'Class',
                'value_options' => $this->getResourceClassPairs(),
            )
        ));

        $this->addPropertyInput($this->getDctermsTitle());
        $this->addPropertyInput($this->getDctermsDescription());
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

    protected function getDctermsTitle()
    {
        return $this->options['dcterms_title'];
    }

    protected function getDctermsDescription()
    {
        return $this->options['dcterms_description'];
    }

    /**
     * Boilerplate to add a text property input
     * @param Omeka\Api\Representation\Entity\PropertyRepresentation $property
     */
    public function addPropertyInput(PropertyRepresentation $property)
    {
        $qName = $property->vocabulary()->getPrefix() . ':' . $property->localName();
        $this->add(array(
            'name'    => $qName . "[0][@value]",
            'type'    => 'Text',
            'options' => array(
                'label' => $property->label()
            ),
            'attributes' => array('data-qname' => $qName)
        ));

        $this->add(array(
            'name'       => $qName . "[0][property_id]",
            'type'       => 'Hidden',
            'attributes' => array(
                'value'      => $property->id(),
                'data-qname' => $qName
            )
        ));
    }

    protected function getEntity()
    {
        if (isset($this->options['entity'])) {
            return $this->options['entity'];
        }
        return false;
    }
}
