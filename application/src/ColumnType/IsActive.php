<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class IsActive implements ColumnTypeInterface
{
    public function getLabel(): string
    {
        return 'Is active'; // @translate
    }

    public function getResourceTypes(): array
    {
        return ['users'];
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
        return 'is_active';
    }

    public function renderHeader(PhpRenderer $view, array $data): string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data): ?string
    {
        return $resource->isActive()
            ? $view->translate('Yes')
            : $view->translate('No');
    }
}
