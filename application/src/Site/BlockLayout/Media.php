<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Media extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Media');
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $htmlPurifier = $this->getServiceLocator()->get('Omeka\htmlPurifier');
        $data = $block->getData();
        $data['text'] = $htmlPurifier->purify($this->getData($data, 'text'));
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $this->thumbnailTypeSelect($view, $site, $block)
            . $this->alignmentClassSelect($view, $site, $block)
            . $this->attachmentsForm($view, $site, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $alignmentClass = $this->getData($block->data(), 'alignment', 'left');
        $thumbnailType = $this->getData($block->data(), 'thumbnail_type', 'square');
        $siteSettings = $this->getServiceLocator()->get('Omeka\SiteSettings');
        $linkType = $siteSettings->get('attachment_link_type', 'item');

        return $view->partial('common/block-layout/file', array(
            'block' => $block,
            'attachments' => $attachments,
            'alignmentClass' => $alignmentClass,
            'thumbnailType' => $thumbnailType,
            'link' => $linkType,
        ));

    }

    public function alignmentClassSelect(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        $alignments = array('left', 'right');
        $data = $block ? $block->data() : [];
        $alignment = $this->getData($data, 'alignment', 'left');
        $select = new Select('o:block[__blockIndex__][o:data][alignment]');
        $select->setValueOptions(array_combine($alignments, $alignments))->setValue($alignment);
        return '<label class="thumbnail-option">Thumbnail Alignment ' . $view->formSelect($select) . '</label>';
    }
}
