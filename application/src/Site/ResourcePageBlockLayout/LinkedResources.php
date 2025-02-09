<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class LinkedResources implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Linked resources'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/linked-resources', ['resource' => $resource]);
    }
}
