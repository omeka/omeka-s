<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class Owner implements ColumnTypeInterface
{
    public function getLabel() : string
    {
        return 'Owner'; // @translate
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
        return 'owner_name';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getlabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string
    {
        $owner = $resource->owner();
        return $owner
            ? $view->hyperlink($owner->name(), $view->url('admin/id', [
                'controller' => 'user',
                'action' => 'show',
                'id' => $owner->id(), ]
            )) : null;
    }
}
