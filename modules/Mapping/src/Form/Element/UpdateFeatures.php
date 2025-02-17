<?php
namespace Mapping\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;

class UpdateFeatures extends Element
{
    protected $formElements;
    protected $labelPropertyElement;
    protected $labelPropertySourceElement;
    protected $imageElement;

    public function setFormElementManager(ServiceLocatorInterface  $formElements)
    {
        $this->formElements = $formElements;
    }

    public function init()
    {
        $this->setAttribute('data-collection-action', 'replace');
        $this->labelPropertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_update_features[label_property]')
            ->setEmptyOption('')
            ->setOptions([
                'prepend_value_options' => ['-1' => '[Remove label]'], // @translate
            ])
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select label property', // @translate
            ]);
        $this->labelPropertySourceElement = (new Element\Radio('mapping_update_features[label_property_source]'))
            ->setValue('item')
            ->setValueOptions([
                'item' => 'Item', // @translate
                'primary_media' => 'Primary media', // @translate
                'assigned_media' => 'Assigned media', // @translate
            ]);
        $this->imageElement = (new Element\Radio('mapping_update_features[image]'))
            ->setValue('')
            ->setValueOptions([
                '' => '[No change]', // @translate
                'unassign' => '[Unassign media]', // @translate
                'primary_media' => 'Primary media', // @translate
            ]);
    }

    public function getLabelPropertyElement()
    {
        return $this->labelPropertyElement;
    }

    public function getLabelPropertySourceElement()
    {
        return $this->labelPropertySourceElement;
    }

    public function getImageElement()
    {
        return $this->imageElement;
    }
}
