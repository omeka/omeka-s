<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\File\Manager as FileManager;
use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class BlockThumbnailTypeSelect extends AbstractHelper
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
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
        $types = $this->fileManager->getThumbnailTypes();
        $type = null;
        if ($block) {
            $type = $block->dataValue('thumbnail_type');
        }

        $select = new Select('o:block[__blockIndex__][o:data][thumbnail_type]');
        $select->setValueOptions(array_combine($types, $types))->setValue($type);
        $html  = '<div class="field"><div class="field-meta">';
        $html .= '<label class="thumbnail-option" for="o:block[__blockIndex__][o:data][thumbnail_type]">' . $view->translate('Thumbnail Type') . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($select) . '</div></div>';
        return $html;
    }
}
