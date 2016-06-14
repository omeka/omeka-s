<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class ItemShowcase extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Item Showcase'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $view->blockThumbnailTypeSelect($block)
            . $view->blockAttachmentsForm($block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        return $view->partial('common/block-layout/item-showcase', array(
            'block' => $block,
            'attachments' => $attachments,
            'thumbnailType' => $thumbnailType,
        ));
    }
}
