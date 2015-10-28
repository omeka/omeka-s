<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class OneItem extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('One Item');
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $this->attachmentsForm($view, $site, $block);
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
                $html .= '<input type="hidden" name="item-caption" data-item-caption="' . $caption . '">';
            }
        }
        $html .= '</div>';

        return $html;
    }
}
