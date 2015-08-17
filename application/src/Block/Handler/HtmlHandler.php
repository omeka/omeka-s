<?php
namespace Omeka\Block\Handler;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element\Textarea;
use Zend\View\Renderer\PhpRenderer;

class HtmlHandler extends AbstractHandler
{
    public function getLabel()
    {
        return 'HTML';
    }

    public function form(PhpRenderer $view, $index, SitePageBlockRepresentation $block = null)
    {
        $textarea = new Textarea("o:block[$index][o:data][html]");
        if ($block) {
            $textarea->setAttribute('value', $block->data()['html']);
        }
        return $view->formField($textarea);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {}
}
