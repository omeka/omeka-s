<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class ItemCount implements ColumnTypeInterface
{
    public function getLabel(): string
    {
        return 'Item count'; // @translate
    }

    public function getResourceTypes(): array
    {
        return ['item_sets'];
    }

    public function getMaxColumns(): ?int
    {
        return 1;
    }

    public function renderDataForm(PhpRenderer $view, array $data): string
    {
        return '';
    }

    public function getSortBy(array $data): ?string
    {
        return 'item_count';
    }

    public function renderHeader(PhpRenderer $view, array $data): string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data): ?string
    {
        $url = $view->url('admin/default', ['controller' => 'item', 'action' => 'browse'], ['query' => ['item_set_id' => $resource->id()]]);
        return $view->hyperlink($resource->itemCount(), $url);
    }
}
