<?php
namespace Mapping\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;

class CopyCoordinates extends Element
{
    protected $formElements;
    protected $copyActionElement;
    protected $propertyElement;
    protected $propertyLatElement;
    protected $propertyLngElement;
    protected $orderElement;
    protected $delimiterElement;
    protected $assignMediaElement;

    public function setFormElementManager(ServiceLocatorInterface  $formElements)
    {
        $this->formElements = $formElements;
    }

    public function init()
    {
        $this->setAttribute('data-collection-action', 'replace');
        $this->copyActionElement = (new Element\Select('mapping_copy_coordinates[copy_action]'))
            ->setEmptyOption('Select copy action')
            ->setValueOptions([
                'by_item_property' => 'Copy from one item property containing both latitude and longitude', // @translate
                'by_item_properties' => 'Copy from two item properties, one latitude and the other longitude', // @translate
                'by_media_property' => 'Copy from one media property containing both latitude and longitude', // @translate
                'by_media_properties' => 'Copy from two media properties, one latitude and the other longitude', // @translate
            ]);
        $this->propertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[property]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select property', // @translate
            ]);
        $this->propertyLatElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[property_lat]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select latitude property', // @translate
            ]);
        $this->propertyLngElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[property_lng]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select longitude property', // @translate
            ]);
        $this->orderElement = (new Element\Radio('mapping_copy_coordinates[order]'))
            ->setValue('latlng')
            ->setValueOptions([
                'latlng' => 'Latitude Longitude', // @translate
                'lnglat' => 'Longitude Latitude', // @translate
            ]);
        $this->delimiterElement = (new Element\Radio('mapping_copy_coordinates[delimiter]'))
            ->setValue(',')
            ->setValueOptions([
                ',' => 'Comma [,]', // @translate
                ' ' => 'Space [ ]', // @translate
                '/' => 'Slash [/]', // @translate
                ':' => 'Colon [:]', // @translate
            ]);
        $this->assignMediaElement = (new Element\Checkbox('mapping_copy_coordinates[assign_media]'));
    }

    public function getCopyActionElement()
    {
        return $this->copyActionElement;
    }

    public function getPropertyElement()
    {
        return $this->propertyElement;
    }

    public function getPropertyLatElement()
    {
        return $this->propertyLatElement;
    }

    public function getPropertyLngElement()
    {
        return $this->propertyLngElement;
    }

    public function getOrderElement()
    {
        return $this->orderElement;
    }

    public function getDelimiterElement()
    {
        return $this->delimiterElement;
    }

    public function getAssignMediaElement()
    {
        return $this->assignMediaElement;
    }
}
