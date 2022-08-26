<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class ResourceClass implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Resource class'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/resource-class', ['resource' => $resource]);
    }
}
