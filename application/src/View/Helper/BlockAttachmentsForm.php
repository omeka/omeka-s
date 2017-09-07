<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering a form for adding/editing block attachments.
 */
class BlockAttachmentsForm extends AbstractHelper
{
    /**
     * Render a form for adding/editing block attachments.
     *
     * The passed title is added to the title element to the head as well as
     * returned inside an h1 for printing on the page.
     *
     * @param SiteBlockAttachmentRepresentation|null $block
     * @param bool $itemOnly If true, selecting an item will immediately attach
     *   it (attachment options will not open)
     * @param array $itemQuery Filter items further using this query
     * @return string
     */
    public function __invoke(SitePageBlockRepresentation $block = null, $itemOnly = false,
        array $itemQuery = [])
    {
        return $this->getView()->partial('common/attachments-form', [
            'block' => $block,
            'itemOnly' => (bool) $itemOnly,
            'itemQuery' => $itemQuery,
        ]);
    }
}
