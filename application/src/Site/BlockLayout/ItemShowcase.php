<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class ItemShowcase extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Item Showcase');
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

        $thumbnailType = $this->getData($block->data(), 'thumbnail_type', 'square');
        return $view->partial('common/block-layout/item-showcase', array(
            'block' => $block,
            'attachments' => $attachments,
            'thumbnailType' => $thumbnailType,
        ));
    }
}
