<?php
namespace NumericDataTypes\FacetedBrowse\FacetType;

use FacetedBrowse\Api\Representation\FacetedBrowseFacetRepresentation;
use FacetedBrowse\FacetType\FacetTypeInterface;
use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use NumericDataTypes\Form\Element\NumericPropertySelect;

class ValueLessThan implements FacetTypeInterface
{
    protected $formElements;

    public function __construct(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Value less than'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['items'];
    }

    public function getMaxFacets() : ?int
    {
        return 1;
    }

    public function prepareDataForm(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-data-form/value-less-than.js', 'NumericDataTypes'));
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        // Property ID
        $propertyId = $this->formElements->get(NumericPropertySelect::class);
        $propertyId->setName('property_id');
        $propertyId->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => '',
            'numeric_data_type' => 'integer',
        ]);
        $propertyId->setAttributes([
            'id' => 'value-less-than-property-id',
            'value' => $data['property_id'] ?? null,
            'data-placeholder' => 'Select one…', // @translate
        ]);
        // Minimum
        $min = $this->formElements->get(LaminasElement\Number::class);
        $min->setName('min');
        $min->setValue($data['min'] ?? null);
        $min->setOptions([
            'label' => 'Minimum value',
        ]);
        $min->setAttributes([
            'id' => 'value-less-than-min',
        ]);
        // Maximum
        $max = $this->formElements->get(LaminasElement\Number::class);
        $max->setName('max');
        $max->setValue($data['max'] ?? null);
        $max->setOptions([
            'label' => 'Maximum value',
        ]);
        $max->setAttributes([
            'id' => 'value-less-than-max',
        ]);
        // Step
        $step = $this->formElements->get(LaminasElement\Number::class);
        $step->setName('step');
        $step->setValue($data['step'] ?? null);
        $step->setOptions([
            'label' => 'Step',
        ]);
        $step->setAttributes([
            'id' => 'value-less-than-step',
        ]);

        return $view->partial('common/faceted-browse/facet-data-form/value-less-than', [
            'propertyId' => $propertyId,
            'min' => $min,
            'max' => $max,
            'step' => $step,
        ]);
    }

    public function prepareFacet(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-render/value-less-than.js', 'NumericDataTypes'));
    }

    public function renderFacet(PhpRenderer $view, FacetedBrowseFacetRepresentation $facet) : string
    {
        $min = $facet->data('min');
        $max = $facet->data('max');
        $step = $facet->data('step') ?: 1;

        // To use a dropdown menu, we must require a min and max so we can
        // calculate a valid range. Also, to avoid the risk of reaching the
        // memory limit, we must set a resonable maximum number of options.
        if (is_numeric($min) && is_numeric($max) && (5000 >= (($max - $min) / $step))) {
            $range = range($min, $max, $step);
            $lessThan = $this->formElements->get(LaminasElement\Select::class);
            $lessThan->setName('value_less_than');
            $lessThan->setEmptyOption('');
            $lessThan->setValueOptions(array_combine($range, $range));
            $lessThan->setAttributes([
                'class' => 'value-less-than',
                'style' => 'width: 50%;',
            ]);
        } else {
            $lessThan = $this->formElements->get(LaminasElement\Number::class);
            $lessThan->setName('value_less_than');
            $lessThan->setAttributes([
                'class' => 'value-less-than',
                'min' => $min,
                'max' => $max,
                'step' => $step,
                'style' => 'width: 50%;',
            ]);
        }
        return $view->partial('common/faceted-browse/facet-render/value-less-than', [
            'facet' => $facet,
            'lessThan' => $lessThan,
        ]);
    }
}
