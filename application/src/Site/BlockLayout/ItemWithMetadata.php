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
        return 'Item with Metadata'; // @translate
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
            return 'foo';
        }

        $html = '';
        foreach($attachments as $attachment) {
            $item = $attachment->item();
            $html .= $item->displayValues();
            if($item->itemSets()) {
                $html .= '<div class="property">';
                    $html .= '<h4>' . $view->translate('Item Sets') . '</h4>';

                    foreach ($item->itemSets() as $itemSet) {
                        $html .= '<div class="value"><a href="' . $view->escapeHtml(($itemSet->url())) . '">' . $itemSet->displayTitle() . '</a></div>';
                    }
                $html .= '</div>';
            }
        }

        if($item->media()) {
            $html .= '<div class="media-list">';

            foreach ($item->media() as $media) {
                $html .= '<a href="' . $media->url() . '" class="media resource">';
                $html .= '<img src="' . $view->escapeHtml(($media->thumbnailUrl('square'))) . '" alt="">';
                $html .= '<span class="media-title">' . $view->escapeHtml(($media->displayTitle())) . '</span>';
                $html .= '</a>';
            }
        }

        $html .= '</div>';
        return $html;
    }
}
