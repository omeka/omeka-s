<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class ItemWithMetadata extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    public function getLabel()
    {
        return 'Item with metadata'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        return $view->blockAttachmentsForm($block, true);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/item-with-metadata')
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return 'No item selected'; // @translate
        }

        return $view->partial($templateViewScript, [
            'block' => $block,
            'attachments' => $attachments,
        ]);
    }
}
