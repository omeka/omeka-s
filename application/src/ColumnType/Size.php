<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class Size implements ColumnTypeInterface
{
    public function getLabel() : string
    {
        return 'Size'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['media'];
    }

    public function getMaxColumns() : ?int
    {
        return 1;
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        return '';
    }

    public function getSortBy(array $data) : ?string
    {
        return 'size';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        return $resource->size();
    }
}
