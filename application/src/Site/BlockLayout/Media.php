<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\Form\Element;
use Laminas\View\Renderer\PhpRenderer;

class Media extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    public function getLabel()
    {
        return 'Media embed'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $layoutSelect = new Element\Select('o:block[__blockIndex__][o:data][layout]');
        $layoutSelect->setOptions([
            'label' => 'Layout',
            'empty_option' => 'Vertical', // @translate
            'value_options' => [
                'horizontal' => 'Horizontal', // @translate
            ],
        ]);
        $layoutSelect->setValue($block ? $block->dataValue('layout') : '');

        $displaySelect = new Element\Select('o:block[__blockIndex__][o:data][media_display]');
        $displaySelect->setOptions([
            'label' => 'Media display',
            'empty_option' => 'Embed media', // @translate
            'value_options' => [
                'thumbnail' => 'Thumbnail only', // @translate
            ],
        ]);
        $displaySelect->setValue($block ? $block->dataValue('media_display') : '');

        $html = '';
        $html .= $view->blockAttachmentsForm($block);
        $html .= '<a href="#" class="collapse" aria-label="collapse"><h4>' . $view->translate('Options') . '</h4></a>';
        $html .= '<div class="collapsible">';
        $html .= $view->formRow($layoutSelect);
        $html .= $view->formRow($displaySelect);
        $html .= $view->blockThumbnailTypeSelect($block);
        $html .= $view->blockShowTitleSelect($block);
        $html .= '</div>';
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/file')
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }
        foreach ($attachments as $key => $attachment) {
            if (!$attachment->item()) {
                unset($attachments[$key]);
            }
        }

        $layout = $block->dataValue('layout');
        $mediaDisplay = $block->dataValue('media_display');
        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        $linkType = $view->siteSetting('attachment_link_type', 'item');
        $showTitleOption = $block->dataValue('show_title_option', 'item_title');

        $classes = ['media-embed'];
        switch ($layout) {
            case 'horizontal':
                $classes[] = 'layout-horizontal';
                break;
            case 'vertical':
            default:
                $classes[] = 'layout-vertical';
        }
        switch ($mediaDisplay) {
            case 'thumbnail':
                $classes[] = 'media-display-thumbnail';
                break;
            case 'embed':
            default:
                $classes[] = 'media-display-embed';
        }

        if (count($attachments) > 3) {
            $classes[] = 'multiple-attachments';
        } else {
            $classes[] = 'attachment-count-' . count($attachments);
        }

        return $view->partial($templateViewScript, [
            'block' => $block,
            'attachments' => $attachments,
            'thumbnailType' => $thumbnailType,
            'link' => $linkType,
            'showTitleOption' => $showTitleOption,
            'classes' => $classes,
            'mediaDisplay' => $mediaDisplay,
        ]);
    }
}
