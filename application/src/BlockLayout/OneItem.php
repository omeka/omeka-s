<?php
namespace Omeka\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class OneItem extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'One Item';
    }

    public function prepareForm(PhpRenderer $view)
    {}

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, SitePageBlockRepresentation $block = null)
    {
        return $this->attachmentForms($view, 1, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $html = '';
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

        return $html;
    }
}
