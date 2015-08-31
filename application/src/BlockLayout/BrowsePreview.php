<?php
namespace Omeka\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\View\Renderer\PhpRenderer;

class BrowsePreview extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Browse Preview';
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, $index, SitePageBlockRepresentation $block = null)
    {
        $text = new Text("o:block[$index][o:data][query]");
        if ($block) {
            $text->setAttribute('value', $this->getData($block->data(), 'query'));
        }
        return 'query: ' . $view->formField($text);
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
            'site/browse', array(), array('query' => $query), true
        ));

        return $preview . $link;
    }
}
