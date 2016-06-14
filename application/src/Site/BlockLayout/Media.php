<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class Media extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Media'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $view->blockThumbnailTypeSelect($block)
            . $this->alignmentClassSelect($view, $block)
            . $view->blockAttachmentsForm($block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $alignmentClass = $block->dataValue('alignment', 'left');
        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        $linkType = $view->siteSetting('attachment_link_type', 'item');

        return $view->partial('common/block-layout/file', array(
            'block' => $block,
            'attachments' => $attachments,
            'alignmentClass' => $alignmentClass,
            'thumbnailType' => $thumbnailType,
            'link' => $linkType,
        ));

    }

    public function alignmentClassSelect(PhpRenderer $view,
        SitePageBlockRepresentation $block = null
    ) {
        $alignments = array('left', 'right');
        $alignment = $block ? $block->dataValue('alignment', 'left') : 'left';
        $select = new Select('o:block[__blockIndex__][o:data][alignment]');
        $select->setValueOptions(array_combine($alignments, $alignments))->setValue($alignment);
        return '<label class="thumbnail-option">Thumbnail Alignment ' . $view->formSelect($select) . '</label>';
    }
}
