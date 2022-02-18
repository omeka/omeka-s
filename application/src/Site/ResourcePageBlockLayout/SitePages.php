<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class SitePages implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Site pages'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/site-pages', [
            'resource' => $resource,
            'site' => $view->site,
        ]);
    }
}
