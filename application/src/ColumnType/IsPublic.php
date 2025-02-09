<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class IsPublic implements ColumnTypeInterface
{
    public function getLabel() : string
    {
        return 'Is public'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['items', 'item_sets', 'media', 'sites'];
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
        return 'is_public';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string
    {
        return $resource->isPublic()
            ? $view->translate('Yes')
            : $view->translate('No');
    }
}
