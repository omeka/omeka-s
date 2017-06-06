<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class LineBreak extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Line break'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        return $this->breakTypeSelect($view, $site, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $breakType = $block->dataValue('break_type');

        return "<div class='break $breakType'></div>";
    }

    public function breakTypeSelect(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        $options = [
            'transparent' => 'Transparent', // @translate
            'opaque' => 'Opaque', // @translate
        ];
        $breakType = $block ? $block->dataValue('break_type', 'transparent') : 'transparent';

        $select = new Select('o:block[__blockIndex__][o:data][break_type]');
        $select->setValueOptions($options)->setValue($breakType);

        $html = '<div class="field">';
        $html .= '<div class="field-meta"><label>' . $view->translate('Break type') . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($select) . '</div>';
        $html .= '</div>';
        return $html;
    }
}
