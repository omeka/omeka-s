<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class GroupClose extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Group close'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '</div>';
    }
}
