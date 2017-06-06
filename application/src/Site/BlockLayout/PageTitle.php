<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class PageTitle extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Page title'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        return $page->title();
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return sprintf('<h2>%s</h2>', $block->page()->title());
    }
}
