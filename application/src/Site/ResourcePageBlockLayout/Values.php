<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class Values implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Values'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'media', 'item_sets'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/values', ['resource' => $resource]);
    }
}
