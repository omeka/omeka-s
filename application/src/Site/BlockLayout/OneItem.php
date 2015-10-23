<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class OneItem extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'One Item';
    }

    public function form(PhpRenderer $view,
        SitePageBlockRepresentation $block = null, SiteRepresentation $site
    ) {
        return $this->attachmentForms($view, 1, $block, $site);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $html = '';
        $html .= '<div class="item resource">';
        $attachments = $block->attachments();
        if ($attachments) {
            $attachment = $attachments[0];
            $item = $attachment->item();

            $html .= $item->link($item->displayTitle());

            $media = $item->primaryMedia();
            if ($media) {
                $html .= '<img src="' . $view->escapeHtml($media->thumbnailUrl('square')) . '">';
            }

            $caption = $attachment->caption();
            if ($caption) {
                $html .= '<span class="caption">' . $caption . '</span>';
            }
        }
        $html .= '</div>';

        return $html;
    }
}
