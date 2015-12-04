<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class ItemList extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Item List');
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $this->thumbnailTypeSelect($view, $site, $block)
            . $this->attachmentsForm($view, $site, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $html = '<div class="item-list">';
        foreach ($attachments as $attachment) {
            $html .= '<div class="item resource">';
            $item = $attachment->item();
            if ($item) {
                $media = $attachment->media();
                if (!$media) {
                    $media = $item->primaryMedia();
                }
                if ($media) {
                    $thumbnailType = $this->getData($block->data(), 'thumbnail_type', 'square');
                    $html .= '<a href="' . $item->url() . '">';
                    $html .= '<img src="' . $view->escapeHtml($media->thumbnailUrl($thumbnailType)) . '"></a>';
                }
                $html .= '<h3>' . $item->link($item->displayTitle()) . '</h3>';
            }
            $caption = $attachment->caption();
            if ($caption) {
                $html .= '<div class="caption">' . $caption . '</div>';
            }
            $html .= '</div>';
        }
        $html .= "</div>";
        return $html;
    }
}
