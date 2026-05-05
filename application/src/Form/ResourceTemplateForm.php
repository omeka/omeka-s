<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourcePickerSelect;
use Laminas\Form\Form;

class ResourceTemplateForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:label',
            ],
        ]);

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourcePickerSelect::class,
            'options' => [
                'label' => 'Suggested class', // @translate
                'resources_endpoint_route_params' => ['controller' => 'resource-class'],
                'api_resource' => 'resource_classes',
            ],
        ]);

        $this->add([
            'name' => 'o:title_property[o:id]',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'title-property-id',
            ],
        ]);
        $this->add([
            'name' => 'o:description_property[o:id]',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'description-property-id',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:label',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'allow_empty' => true,
        ]);
    }
}
