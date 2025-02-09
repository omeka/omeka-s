<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class Modified implements ColumnTypeInterface
{
    public function getLabel() : string
    {
        return 'Modified'; // @translate
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
        return 'modified';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getlabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string
    {
        return $view->i18n()->dateFormat($resource->modified());
    }
}
