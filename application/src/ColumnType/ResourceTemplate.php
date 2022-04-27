<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class ResourceTemplate implements ColumnTypeInterface
{
    protected FormElementManager $formElements;

    public function __construct(FormElementManager $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Resource template'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getMaxColumns() : ?int
    {
        return 1;
    }

    public function dataIsValid(array $data) : bool
    {
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
        return 'resource_template_label';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        return $view->translate($resource->displayResourceTemplateLabel());
    }
}
