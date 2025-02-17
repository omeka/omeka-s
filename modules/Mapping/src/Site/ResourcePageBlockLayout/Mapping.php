<?php
namespace Mapping\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;
use Laminas\View\Renderer\PhpRenderer;

class Mapping implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Mapping'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/mapping-resource-map', ['resource' => $resource]);
    }
}
