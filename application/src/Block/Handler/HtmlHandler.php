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

    public function prepare(PhpRenderer $view)
    {
        $view->headscript()->appendFile($view->assetUrl('js/ckeditor/ckeditor.js', 'Omeka'));
        $view->headscript()->appendFile($view->assetUrl('js/ckeditor/adapters/jquery.js', 'Omeka'));
    }

    public function form(PhpRenderer $view, $index, SitePageBlockRepresentation $block = null)
    {
        $textarea = new Textarea("o:block[$index][o:data][html]");
        $textarea->setAttribute('class', 'block-html');
        if ($block) {
            $textarea->setAttribute('value', $block->data()['html']);
        }
        $script = '<script type="text/javascript">
            $(".block-html").ckeditor({customConfig: "' . $view->assetUrl('js/ckeditor_config.js', 'Omeka') . '"});
        </script>';
        return $view->formField($textarea) . $script;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {}
}
