<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering an attachment title display select element.
 */
class BlockShowTitleSelect extends AbstractHelper
{
    /**
     * Render an attachment title display select element.
     *
     * @param SiteBlockAttachmentRepresentation|null $block
     * @return string
     */
    public function __invoke(SitePageBlockRepresentation $block = null)
    {
        $view = $this->getView();

        $titleOptions = [
            'item_title' => 'item title', // @translate
            'file_name' => 'file name', // @translate
            'no_title' => 'no title', // @translate
        ];
        $titleSelectedOption = $block ? $block->dataValue('show_title_option', 'item_title') : 'item_title';
        $titleSelect = new Select('o:block[__blockIndex__][o:data][show_title_option]');
        $titleSelect->setValueOptions($titleOptions)->setValue($titleSelectedOption);
        $showTitleOption = $block ? $block->dataValue('show_title_option', 'transparent') : 'transparent';

        $html = '<div class="field">';
        $html .= '<div class="field-meta"><label>' . $view->translate('Show attachment title') . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($titleSelect) . '</div>';
        $html .= '</div>';
        return $html;
    }
}
