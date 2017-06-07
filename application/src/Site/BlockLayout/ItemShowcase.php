<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class ItemShowcase extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Item showcase'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $html = '';
        $html .= $view->blockAttachmentsForm($block);

        $html .= '<a href="#" class="collapse" aria-label="collapse"><h4>' . $view->translate('Options'). '</h4></a>';
        $html .= '<div class="collapsible">';
        $html .= $view->blockThumbnailTypeSelect($block);
        $html .= $view->blockShowTitleSelect($block);
        $html .= '</div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        $showTitleOption = $block->dataValue('show_title_option', 'item_title');
        return $view->partial('common/block-layout/item-showcase', [
            'block' => $block,
            'attachments' => $attachments,
            'thumbnailType' => $thumbnailType,
            'showTitleOption' => $showTitleOption,
        ]);
    }
}
