<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\View\Renderer\PhpRenderer;

interface TemplateableBlockLayoutInterface extends BlockLayoutInterface
{
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = null);
}
