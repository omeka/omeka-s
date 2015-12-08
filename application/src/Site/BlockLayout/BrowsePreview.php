<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\View\Renderer\PhpRenderer;

class BrowsePreview extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Browse Preview');
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        $text = new Text("o:block[__blockIndex__][o:data][query]");
        $heading = new Text("o:block[__blockIndex__][o:data][heading]");
        $linkText = new Text("o:block[__blockIndex__][o:data][link-text]");

        if ($block) {
            $text->setAttribute('value', $this->getData($block->data(), 'query'));
            $heading->setAttribute('value', $this->getData($block->data(), 'heading'));
            $linkText->setAttribute('value', $this->getData($block->data(), 'link-text'));
        }
        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Query') . '</label>';
        $html .= '<div class="field-description">' . $view->translate('Display resources using this search query') . '</div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($text) . '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Preview Title') . '</label>';
        $html .= '<div class="field-description">' . $view->translate('Heading above resource list') . '</div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($heading) . '</div>';

        $html .= '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Browse link text') . '</label>';
        $html .= '<div class="field-description">' . $view->translate('Text for link to full browse view') . '</div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($linkText) . '</div></div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        parse_str($this->getData($block->data(), 'query'), $query);
        $heading = $this->getData($block->data(), 'heading');
        $linkText = $this->getData($block->data(), 'link-text');

        $previewQuery = $query;
        $previewQuery['limit'] = 10;
        $previewQuery['site_id'] = $block->page()->site()->id();

        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', $previewQuery);


        $preview = '<div class="preview-block">';
        if ($heading) {
            $preview .= '<h2>' . $heading . '</h2>';
        }
        $preview .= '<ul class="resource-list preview">';
        foreach ($response->getContent() as $item) {
            $preview .= '<li class="item resource">';
            if ($primaryMedia = $item->primaryMedia()) {
            $preview .= '<img src="' . $view->escapeHtml($primaryMedia->thumbnailUrl('square')) . '" "title="' . $view->escapeHtml($primaryMedia->displayTitle()) . '" alt="' . $view->escapeHtml($primaryMedia->mediaType()) . ' thumbnail">';
            }
            $preview .= '<h4>' . $item->link($item->displayTitle()) . '</h4>';
            $preview .= '<div class="description">' . $item->displayDescription() . '</div>';
            $preview .= '</li>';
        }
        $preview .= '</ul>';

        if ($linkText == '') {
            $linkText = $view->translate('Browse all');
        }
        $preview .= $view->hyperlink($linkText, $view->url(
            'site/resource', ['controller' => 'item', 'action' => 'browse'], ['query' => $query], true
        ));
        $preview .= '</div>';

        return $preview;
    }
}
