<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element\Number;
use Zend\Form\Element\Radio;
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
        $resourceType = new Radio("o:block[__blockIndex__][o:data][resource_type]");
        $resourceType->setValueOptions([
            'items' => $view->translate('Items'),
            'item_sets' => $view->translate('Item sets'),
            'media' => $view->translate('Media'),
        ]);
        $text = new Text("o:block[__blockIndex__][o:data][query]");
        $heading = new Text("o:block[__blockIndex__][o:data][heading]");
        $limit = new Number("o:block[__blockIndex__][o:data][limit]");
        $linkText = new Text("o:block[__blockIndex__][o:data][link-text]");
        $expandButton = '<a href="#" class="expand" aria-label="' . $view->translate('Expand'). '" title="' . $view->translate('Expand') . '"></a>';

        if ($block) {
            $resourceType->setAttribute('value', $block->dataValue('resource_type'));
            $text->setAttribute('value', $block->dataValue('query'));
            $heading->setAttribute('value', $block->dataValue('heading'));
            $limit->setAttribute('value', $block->dataValue('limit'));
            $linkText->setAttribute('value', $block->dataValue('link-text'));
        } else {
            $resourceType->setAttribute('value', 'items');
            $linkText->setAttribute('value', $view->translate('Browse all'));
            $limit->setAttribute('value', 12);
        }

        $html = '';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Resource type') . '</label>';
        $html .= '<a href="#" class="expand"></a>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($resourceType) . '</div>';
        $html .= '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Query') . '</label>';
        $html .= $expandButton;
        $html .= '<div class="collapsible"><div class="field-description">' . $view->translate('Display resources using this search query') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($text) . '</div>';
        $html .= '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Preview title') . '</label>';
        $html .= $expandButton;
        $html .= '<div class="collapsible"><div class="field-description">' . $view->translate('Translatable heading above resource list') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($heading) . '</div>';
        $html .= '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Max number') . '</label>';
        $html .= '<a href="#" class="expand"></a>';
        $html .= '<div class="collapsible"><div class="field-description">' . $view->translate('Maximum number of resources to display in the preview.') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($limit) . '</div>';
        $html .= '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Browse link text') . '</label>';
        $html .= $expandButton;
        $html .= '<div class="collapsible"><div class="field-description">' . $view->translate('Translatable text for link to full browse view') . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($linkText) . '</div>';
        $html .= '</div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $resourceType = $block->dataValue('resource_type');

        parse_str($block->dataValue('query'), $query);
        $originalQuery = $query;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $itemPool['site_attachments_only'] = true;
        }

        $query['site_id'] = $site->id();
        $query['limit'] = $block->dataValue('limit') ?: 12;

        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'created';
        }
        if (!isset($query['sort_order'])) {
            $query['sort_order'] = 'desc';
        }

        $response = $view->api()->search($resourceType, $query);
        $resources = $response->getContent();

        $resourceTypes = [
            'items' => 'item',
            'item_sets' => 'item-set',
            'media' => 'media',
        ];

        return $view->partial('common/block-layout/browse-preview', [
            'resourceType' => $resourceTypes[$resourceType],
            'resources' => $resources,
            'heading' => $block->dataValue('heading'),
            'linkText' => $block->dataValue('link-text'),
            'query' => $originalQuery,
        ]);
    }
}
