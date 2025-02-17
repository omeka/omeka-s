<?php
namespace NumericDataTypes\FacetedBrowse\FacetType;

use FacetedBrowse\Api\Representation\FacetedBrowseFacetRepresentation;
use FacetedBrowse\FacetType\FacetTypeInterface;
use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use NumericDataTypes\DataType\Timestamp;
use NumericDataTypes\Form\Element\NumericPropertySelect;

class DateInInterval implements FacetTypeInterface
{
    protected $formElements;

    public function __construct(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Date in interval'; // @translate
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
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-data-form/date-in-interval.js', 'NumericDataTypes'));
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        // Property ID
        $propertyId = $this->formElements->get(NumericPropertySelect::class);
        $propertyId->setName('property_id');
        $propertyId->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => '',
            'numeric_data_type' => 'interval',
        ]);
        $propertyId->setAttributes([
            'id' => 'date-in-interval-property-id',
            'value' => $data['property_id'] ?? null,
            'data-placeholder' => 'Select one…', // @translate
        ]);
        // Values
        $values = $this->formElements->get(LaminasElement\Textarea::class);
        $values->setName('values');
        $values->setOptions([
            'label' => 'Values', // @translate
            'info' => 'Enter the date/time values, separated by a new line. For each line, enter the date/time in ISO 8601 format, followed by a space, followed by the human-readable date/time.', // @translate
        ]);
        $values->setAttributes([
            'id' => 'date-in-interval-values',
            'style' => 'height: 300px;',
            'value' => $data['values'] ?? null,
        ]);
        return $view->partial('common/faceted-browse/facet-data-form/date-in-interval', [
            'elementPropertyId' => $propertyId,
            'elementValues' => $values,
        ]);
    }

    public function prepareFacet(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile($view->assetUrl('js/faceted-browse/facet-render/date-in-interval.js', 'NumericDataTypes'));
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
                Timestamp::getDateTimeFromValue($iso8601);
            } catch (\InvalidArgumentException $e) {
                // This is invalid ISO 8601.
                continue;
            }
            $iso8601KeyValues[$iso8601] = $value;
        }
        $values = $iso8601KeyValues;

        $elementValues = $this->formElements->get(LaminasElement\Select::class);
        $elementValues->setName('date_in_interval');
        $elementValues->setAttribute('class', 'date-in-interval-value');
        $elementValues->setAttribute('style', 'width: 90%;');
        $elementValues->setEmptyOption('Select a date…'); // @translate
        $elementValues->setValueOptions($values);

        return $view->partial('common/faceted-browse/facet-render/date-in-interval', [
            'facet' => $facet,
            'elementValues' => $elementValues,
        ]);
    }
}
