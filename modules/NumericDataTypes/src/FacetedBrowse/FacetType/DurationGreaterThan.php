<?php
namespace NumericDataTypes\FacetedBrowse\FacetType;

use FacetedBrowse\Api\Representation\FacetedBrowseFacetRepresentation;
use FacetedBrowse\FacetType\FacetTypeInterface;
use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use NumericDataTypes\DataType\Duration;
use NumericDataTypes\Form\Element\NumericPropertySelect;

class DurationGreaterThan implements FacetTypeInterface
{
    protected $formElements;

    public function __construct(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Duration greater than'; // @translate
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
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-data-form/duration-greater-than.js', 'NumericDataTypes'));
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        // Property ID
        $propertyId = $this->formElements->get(NumericPropertySelect::class);
        $propertyId->setName('property_id');
        $propertyId->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => '',
            'numeric_data_type' => 'duration',
        ]);
        $propertyId->setAttributes([
            'id' => 'duration-greater-than-property-id',
            'value' => $data['property_id'] ?? null,
            'data-placeholder' => 'Select one…', // @translate
        ]);
        // Values
        $values = $this->formElements->get(LaminasElement\Textarea::class);
        $values->setName('values');
        $values->setOptions([
            'label' => 'Values', // @translate
            'info' => 'Enter the duration values, separated by a new line. For each line, enter the duration in ISO 8601 format, followed by a space, followed by the human-readable duration.', // @translate
        ]);
        $values->setAttributes([
            'id' => 'duration-greater-than-values',
            'style' => 'height: 300px;',
            'value' => $data['values'] ?? null,
        ]);
        return $view->partial('common/faceted-browse/facet-data-form/duration-greater-than', [
            'elementPropertyId' => $propertyId,
            'elementValues' => $values,
        ]);
    }

    public function prepareFacet(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-render/duration-greater-than.js', 'NumericDataTypes'));
    }

    public function renderFacet(PhpRenderer $view, FacetedBrowseFacetRepresentation $facet) : string
    {
        $values = $facet->data('values');
        $values = explode("\n", $values);
        $values = array_map('trim', $values);
        $values = array_unique($values);
        $iso8601KeyValues = [];
        foreach ($values as $value) {
            if (preg_match('/^([^\s]+) (.+)/', $value, $matches)) {
                $iso8601 = $matches[1];
                $value = $matches[2];
            } else {
                $iso8601 = $value;
                $value = $value;
            }
            try {
                Duration::getDurationFromValue($iso8601);
            } catch (\InvalidArgumentException $e) {
                // This is invalid ISO 8601.
                continue;
            }
            $iso8601KeyValues[$iso8601] = $value;
        }
        $values = $iso8601KeyValues;

        $elementValues = $this->formElements->get(LaminasElement\Select::class);
        $elementValues->setName('duration_greater_than');
        $elementValues->setAttribute('class', 'duration-greater-than-value');
        $elementValues->setAttribute('style', 'width: 90%;');
        $elementValues->setEmptyOption('Select a date…'); // @translate
        $elementValues->setValueOptions($values);

        return $view->partial('common/faceted-browse/facet-render/duration-greater-than', [
            'facet' => $facet,
            'elementValues' => $elementValues,
        ]);
    }
}
