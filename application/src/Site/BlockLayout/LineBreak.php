<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class LineBreak extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Line Break');
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $this->breakTypeSelect($view, $site, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $breakType = $this->getData($block->data(), 'break_type');

        return "<div class='break $breakType'></div>";

    }

    public function breakTypeSelect(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        $translator = $this->getServiceLocator()->get('MvcTranslator');

        $breakTypeValues = array('transparent', 'opaque');
        $breakTypeLabels = array($translator->translate('Transparent'), $translator->translate('Opaque'));
        $data = $block ? $block->data() : [];
        $breakType = $this->getData($data, 'break_type', 'transparent');

        $select = new Select('o:block[__blockIndex__][o:data][break_type]');
        $select->setValueOptions(array_combine($breakTypeValues, $breakTypeLabels))->setValue($breakType);

        $html  = '<div class="field">';
        $html .= '<div class="field-meta"><label>' . $translator->translate('Break Type') . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($select) . '</div>';
        $html .= '</div>';
        return $html;
    }
}
