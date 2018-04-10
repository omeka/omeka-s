<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering a thumbnail type select element.
 */
class BlockThumbnailTypeSelect extends AbstractHelper
{
    /**
     * @var array
     */
    protected $thumbnailTypes;

    /**
     * Construct the helper.
     *
     * @param array $thumbnailTypes
     */
    public function __construct(array $thumbnailTypes)
    {
        $this->thumbnailTypes = $thumbnailTypes;
    }

    /**
     * Render a thumbnail type select element.
     *
     * @param SiteBlockAttachmentRepresentation|null $block
     * @return string
     */
    public function __invoke(SitePageBlockRepresentation $block = null)
    {
        $view = $this->getView();
        $type = null;
        if ($block) {
            $type = $block->dataValue('thumbnail_type');
        }

        $selectLabel = $view->translate('Thumbnail type');
        $select = new Select('o:block[__blockIndex__][o:data][thumbnail_type]');
        $select->setValueOptions(array_combine($this->thumbnailTypes, $this->thumbnailTypes))->setValue($type);
        $select->setAttributes(['title' => $selectLabel, 'aria-label' => $selectLabel]);
        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label class="thumbnail-option" for="o:block[__blockIndex__][o:data][thumbnail_type]">' . $selectLabel . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($select) . '</div></div>';
        return $html;
    }
}
