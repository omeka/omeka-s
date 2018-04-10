<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class Media extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Media'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $html = '';
        $html .= $view->blockAttachmentsForm($block);
        $html .= '<a href="#" class="collapse" aria-label="collapse"><h4>' . $view->translate('Options'). '</h4></a>';
        $html .= '<div class="collapsible">';
        $html .= $view->blockThumbnailTypeSelect($block);
        $html .= $this->alignmentClassSelect($view, $block);
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

        $alignmentClass = $block->dataValue('alignment', 'left');
        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        $linkType = $view->siteSetting('attachment_link_type', 'item');
        $showTitleOption = $block->dataValue('show_title_option', 'item_title');

        return $view->partial('common/block-layout/file', [
            'block' => $block,
            'attachments' => $attachments,
            'alignmentClass' => $alignmentClass,
            'thumbnailType' => $thumbnailType,
            'link' => $linkType,
            'showTitleOption' => $showTitleOption,
        ]);
    }

    public function alignmentClassSelect(PhpRenderer $view,
        SitePageBlockRepresentation $block = null
    ) {
        $alignments = ['left', 'right'];
        $alignment = $block ? $block->dataValue('alignment', 'left') : 'left';
        $select = new Select('o:block[__blockIndex__][o:data][alignment]');
        $select->setValueOptions(array_combine($alignments, $alignments))->setValue($alignment);
        $selectLabel = 'Thumbnail alignment'; // @translate
        $select->setAttributes(['title' => $selectLabel, 'aria-label' => $selectLabel]);
        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label class="thumbnail-option" for="o:block[__blockIndex__][o:data][alignment]">' . $selectLabel . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($select) . '</div></div>';
        return $html;
    }
}
