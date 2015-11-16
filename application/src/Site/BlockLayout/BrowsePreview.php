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
        if ($block) {
            $text->setAttribute('value', $this->getData($block->data(), 'query'));
        }
        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Query') . '</label>';
        $html .= '<div class="field-description">' . $view->translate('Display resources using this search query') . '</div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formField($text) . '</div></div>';
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        parse_str($this->getData($block->data(), 'query'), $query);
        $previewQuery = $query;
        $previewQuery['limit'] = 10;
        $previewQuery['site_id'] = $block->page()->site()->id();

        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', $previewQuery);

        $preview = '<ul>';
        foreach ($response->getContent() as $item) {
            $preview .= '<li>' . $item->displayTitle() . '</li>';
        }
        $preview .= '</ul>';

        $link = $view->hyperlink('browse this', $view->url(
            'site/resource', ['controller' => 'item'], ['query' => $query], true
        ));

        return $preview . $link;
    }
}
