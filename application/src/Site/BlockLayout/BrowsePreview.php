<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element\Text;
use Zend\View\Renderer\PhpRenderer;

class BrowsePreview extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Browse preview'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $text = new Text("o:block[__blockIndex__][o:data][query]");
        $heading = new Text("o:block[__blockIndex__][o:data][heading]");
        $linkText = new Text("o:block[__blockIndex__][o:data][link-text]");

        if ($block) {
            $text->setAttribute('value', $block->dataValue('query'));
            $heading->setAttribute('value', $block->dataValue('heading'));
            $linkText->setAttribute('value', $block->dataValue('link-text'));
        }

        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Query') . '</label>';
        $html .= '<a href="#" class="expand"></a>';
        $html .= '<div class="collapsible"><div class="field-description">' . $view->translate('Display resources using this search query') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($text) . '</div>';
        $html .= '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Preview title') . '</label>';
        $html .= '<a href="#" class="expand"></a><div class="collapsible"><div class="field-description">' . $view->translate('Heading above resource list') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($heading) . '</div>';
        $html .= '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Browse link text') . '</label>';
        $html .= '<a href="#" class="expand"></a>';
        $html .= '<div class="collapsible"><div class="field-description">' . $view->translate('Text for link to full browse view') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($linkText) . '</div>';
        $html .= '</div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        parse_str($block->dataValue('query'), $query);
        $originalQuery = $query;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $itemPool['site_attachments_only'] = true;
        }
        $query['site_id'] = $site->id();
        $query['sort_by'] = 'created';
        $query['sort_order'] = 'desc';
        $query['limit'] = 10;

        $response = $view->api()->search('items', $query);
        $items = $response->getContent();

        return $view->partial('common/block-layout/browse-preview', [
            'items' => $items,
            'heading' => $block->dataValue('heading'),
            'linkText' => $block->dataValue('link-text'),
            'query' => $originalQuery,
        ]);
    }
}
