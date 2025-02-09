<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractEntityRepresentation;
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

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        $propertySelect = $this->formElements->get(OmekaElement\PropertySelect::class);
        $propertySelect->setName('property_term');
        $propertySelect->setOptions([
            'label' => 'Property', // @translate
            'empty_option' => 'Select a property…',
            'term_as_value' => true,
        ]);
        $propertySelect->setAttributes([
            'value' => $data['property_term'] ?? null,
            'required' => true,
            'data-column-data-key' => 'property_term',
        ]);

        $maxValuesInput = $this->formElements->get(LaminasElement\Number::class);
        $maxValuesInput->setName('max_values');
        $maxValuesInput->setOptions([
            'label' => 'Max values', // @translate
            'info' => 'Enter the maximum number of values to display. Set to blank to display all values.', // @translate
        ]);
        $maxValuesInput->setAttributes([
            'value' => $data['max_values'] ?? 1,
            'min' => 1,
            'step' => 1,
            'data-column-data-key' => 'max_values',
        ]);

        return sprintf('%s%s', $view->formRow($propertySelect), $view->formRow($maxValuesInput));
    }

    public function getSortBy(array $data) : ?string
    {
        return $data['property_term'] ?? null;
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        if (!isset($data['property_term']) || '' === trim($data['property_term'])) {
            return $this->getLabel();
        }
        $response = $this->api->search('properties', ['term' => $data['property_term']])->getContent();
        return $response ? $response[0]->label() : $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string
    {
        if (!isset($data['property_term'])) {
            return null;
        }
        // Get the values.
        $values = $resource->value($data['property_term'], ['all' => true]);
        if ($data['max_values']) {
            $values = array_slice($values, 0, $data['max_values']);
        }
        // Prepare the content.
        $content = [];
        foreach ($values as $value) {
            $content[] = $value->asHtml();
        }
        return $content ? implode('<br>', $content) : null;
    }
}
