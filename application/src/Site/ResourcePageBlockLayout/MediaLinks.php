<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class MediaLinks implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Media links'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/media-links', ['resource' => $resource]);
    }
}
