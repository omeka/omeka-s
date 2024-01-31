<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class PageTitle extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    public function getLabel()
    {
        return 'Page title'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        return $view->escapeHtml($page->title());
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/page-title')
    {
        return $view->partial($templateViewScript, [
            'block' => $block,
            'pageTitle' => $block->page()->title(),
        ]);
    }
}
