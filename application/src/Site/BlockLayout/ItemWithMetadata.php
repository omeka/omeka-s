<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class ItemWithMetadata extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Item with metadata'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        return $view->blockAttachmentsForm($block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return 'No item selected'; // @translate
        }

        return $view->partial('common/block-layout/item-with-metadata', [
            'attachments' => $attachments,
        ]);
    }
}
