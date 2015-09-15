<?php
namespace Omeka\BlockLayout;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
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
     * Render all forms for adding/editing block attachments.
     *
     * @param PhpRenderer $view
     * @param int $numAttachments The number of attachments this layout holds
     * @param SitePageBlockRepresentation|null $block
     * @return string
     */
    public function attachmentForms(PhpRenderer $view, $numAttachments,
        SitePageBlockRepresentation $block = null
    ) {
        $attachments = $block ? $block->attachments() : array();
        $html = '<div class="attachments">';
        for ($i = 1; $i <= $numAttachments; $i++) {
            if ($attachment = current($attachments)) {
                next($attachments);
            } else {
                $attachment = null;
            }
            $html .= $this->attachmentForm($view, $attachment);
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a form for adding/editing a block attachment.
     *
     * @param PhpRenderer $view
     * @param SiteBlockAttachmentRepresentation|null $block
     * @return string
     */
    public function attachmentForm(PhpRenderer $view,
        SiteBlockAttachmentRepresentation $attachment = null
    ) {
        $id = null;
        $caption = null;
        $sidebarContentUrl = $view->url('admin/default', array(
            'controller' => 'item', 'action' => 'sidebar-select',
        ));
        $title = '<button class="item-select" data-sidebar-content-url="' . $sidebarContentUrl . '">Select Item</button>';
        if ($attachment) {
            $id = $attachment->item()->id();
            $title = $attachment->item()->displayTitle();
            $caption = $attachment->caption();
        }
        $html = '
<div class="attachment">
    <div class="field">
        <div class="field-meta">
            <label>Item</label>
        </div>
        <div class="inputs">
            <div class="item-title">' . $title . '</div>
        </div>
    </div>
    <div class="field">
        <div class="field-meta">
            <label>Caption</label>
        </div>
        <div class="inputs">
            <textarea class="caption" name="o:block[blockIndex][o:attachment][attachmentIndex][o:caption]">' . $caption . '</textarea>
        </div>
    </div>
    <input type="hidden" class="item" name="o:block[blockIndex][o:attachment][attachmentIndex][o:item][o:id]" value="' . $id . '">
</div>';
        return $html;
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
