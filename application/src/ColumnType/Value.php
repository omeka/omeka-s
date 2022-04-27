<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

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
        return '';
    }

    public function getSortBy(array $data) : ?string
    {
        return $data['property_term'];
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        $property = $this->api->search('properties', ['term' => $data['property_term']])->getContent()[0];
        return $property->label();
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        return $resource->value($data['property_term']);
    }
}
