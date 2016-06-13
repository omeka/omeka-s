<?php
namespace Omeka\Site\BlockLayout;

use Zend\Form\Element\Select;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SiteBlockAttachmentRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

abstract class AbstractBlockLayout implements BlockLayoutInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function prepareForm(PhpRenderer $view)
    {}

    /**
     * {@inheritDoc}
     */
    public function prepareRender(PhpRenderer $view)
    {}

    /**
     * {@inheritDoc}
     */
    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    /**
     * Render a form for adding/editing block attachments.
     *
     * @param PhpRenderer $view
     * @param SiteRepresentation $site
     * @param SiteBlockAttachmentRepresentation|null $block
     * @param bool $itemOnly If true, selecting an item will immediately attach
     *   it (attachment options will not open)
     * @param array $itemQuery Filter items further using this query
     * @return string
     */
    public function attachmentsForm(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null, $itemOnly = false,
        array $itemQuery = []
    ) {
        return $view->partial('common/attachments-form', [
            'block' => $block,
            'itemOnly' => (bool) $itemOnly,
            'itemQuery' => $itemQuery,
        ]);
    }

    /**
     * Return a thumbnail type select element.
     *
     * @param PhpRenderer $view
     * @param SiteRepresentation $site
     * @param SiteBlockAttachmentRepresentation|null $block
     * @return string
     */
    public function thumbnailTypeSelect(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        $types = $this->getServiceLocator()->get('Omeka\File\Manager')->getThumbnailTypes();
        $type = null;
        if ($block) {
            $type = $this->getData($block->data(), 'thumbnail_type');
        }

        $select = new Select('o:block[__blockIndex__][o:data][thumbnail_type]');
        $select->setValueOptions(array_combine($types, $types))->setValue($type);
        return '<label class="thumbnail-option">Thumbnail Type ' . $view->formSelect($select) . '</label>';
    }

    /**
     * Return block data by key.
     *
     * @param array $data The block data
     * @param string $key The data key
     * @param mixed $default Return this if key does not exist
     * @return mixed
     */
    public function getData(array $data, $key, $default = null)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
}
