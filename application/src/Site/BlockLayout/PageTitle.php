<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class PageTitle extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Page Title'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $block->page()->title();
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return sprintf('<h2>%s</h2>', $block->page()->title());

    }
}
