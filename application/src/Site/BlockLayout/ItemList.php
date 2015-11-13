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

        $html = '';
        foreach ($attachments as $attachment) {
            $html .= '<div>';
            $item = $attachment->item();
            if ($item) {
                $html .= '<h2>' . $item->link($item->displayTitle()) . '</h2>';
                $media = $attachment->media();
                if (!$media) {
                    $media = $item->primaryMedia();
                }
                if ($media) {
                    $thumbnailType = $this->getData($block->data(), 'thumbnail_type', 'square');
                    $html .= '<h3>' . $media->link($media->displayTitle()) . '</h3>';
                    $html .= '<img src="' . $view->escapeHtml($media->thumbnailUrl($thumbnailType)) . '">';
                }
            }
            $caption = $attachment->caption();
            if ($caption) {
                $html .= '<p>' . $caption . '</p>';
            }
            $html .= '</div>';
        }
        return $html;
    }
}
