<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Form\Element as OmekaElement;

class Value implements ColumnTypeInterface
{
    protected FormElementManager $formElements;

    protected ApiManager $api;

    public function __construct(FormElementManager $formElements, ApiManager $api)
    {
        $this->formElements = $formElements;
        $this->api = $api;
    }

    public function getLabel() : string
    {
        return 'Value'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getMaxColumns() : ?int
    {
        return null;
    }

    public function dataIsValid(array $data) : bool
    {
        if (!isset($data['property_term'])) {
            return false;
        }
        $response = $this->api->search('properties', ['term' => $data['property_term'], 'limit' => 0]);
        if (!$response->getTotalResults()) {
            return false;
        }
        return true;
    }

    public function prepareDataForm(PhpRenderer $view) : void
    {
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        $propertySelect = $this->formElements->get(OmekaElement\PropertySelect::class);
        $propertySelect->setName('property_term');
        $propertySelect->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => '',
            'term_as_value' => true,
        ]);
        $propertySelect->setAttributes([
            'id' => 'value-property-terms',
            'value' => $data['property_term'] ?? null,
            'data-placeholder' => 'Select a property…', // @translate
            'required' => true,
        ]);

        $maxValuesInput = $this->formElements->get(LaminasElement\Number::class);
        $maxValuesInput->setName('max_values');
        $maxValuesInput->setOptions([
            'label' => 'Max values', // @translate
            'info' => 'Enter the maximum number of values to display. Set to blank to display all values.', // @translate
        ]);
        $maxValuesInput->setAttributes([
            'id' => 'value-max-values',
            'value' => $data['max_values'] ?? 1,
            'min' => 1,
            'step' => 1,
        ]);

        return sprintf('%s%s', $view->formRow($propertySelect), $view->formRow($maxValuesInput));
    }

    public function getSortBy(array $data) : ?string
    {
        return $data['property_term'];
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        if (!isset($data['property_term'])) {
            return $this->getLabel();
        }
        $property = $this->api->search('properties', ['term' => $data['property_term']])->getContent()[0];
        return $property->label();
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        if (!isset($data['property_term'])) {
            return null;
        }
        return $resource->value($data['property_term']);
    }
}
