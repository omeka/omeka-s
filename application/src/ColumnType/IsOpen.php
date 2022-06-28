<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class IsOpen implements ColumnTypeInterface
{
    public function getLabel() : string
    {
        return 'Is open'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['item_sets'];
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
        return 'is_open';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string
    {
        return $resource->isOpen()
            ? $view->translate('Yes')
            : $view->translate('No');
    }
}
