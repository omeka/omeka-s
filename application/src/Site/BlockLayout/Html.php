<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Textarea;
use Zend\View\Renderer\PhpRenderer;

class Html extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('HTML');
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $htmlPurifier = $this->getServiceLocator()->get('Omeka\HtmlPurifier');
        $data = $block->getData();
        $data['html'] = $htmlPurifier->purify($this->getData($data, 'html'));
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
        return $view->formRow($textarea);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return $this->getData($block->data(), 'html');
    }
}
