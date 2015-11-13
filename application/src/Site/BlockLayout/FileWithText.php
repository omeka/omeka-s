<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Textarea;
use Zend\View\Renderer\PhpRenderer;

class FileWithText extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('File with Text');
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
        $textarea = new Textarea("o:block[__blockIndex__][o:data][html]");
        $textarea->setAttribute('class', 'block-html full wysiwyg');
        if ($block) {
            $textarea->setAttribute('value', $this->getData($block->data(), 'html'));
        }

        return $this->thumbnailTypeSelect($view, $site, $block)
            . $this->attachmentsForm($view, $site, $block)
            . $view->formField($textarea);

    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $html = '<div>';
        foreach($attachments as $attachment) {
            $html .= '<div>';
            $item = $attachment->item();
            if ($item) {
                $media = $attachment->media();
                if (!$media) {
                    $media = $item->primaryMedia();
                }
                if ($media) {
                    $thumbnailType = $this->getData($block->data(), 'thumbnail_type', 'square');
                    $html .= '<img src="' . $view->escapeHtml($media->thumbnailUrl($thumbnailType)) . '">';
                }
            }
            $caption = $attachment->caption();
            if ($caption) {
                $html .= '<p>' . $caption . '</p>';
            }
            $html .= '</div>';
        }
        $html .= $this->getData($block->data(), 'html');
        $html .= '</div>';

        return $html;

    }
}
