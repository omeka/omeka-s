<?php
namespace Omeka\Site\BlockLayout;

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
     * Render all forms for adding/editing block attachments.
     *
     * @param PhpRenderer $view
     * @param int $numAttachments The number of attachments this layout holds
     * @param SitePageBlockRepresentation|null $block
     * @param SiteRepresentation $site
     * @return string
     */
    public function attachmentForms(PhpRenderer $view, $numAttachments,
        SitePageBlockRepresentation $block = null, SiteRepresentation $site
    ) {
        $attachments = $block ? $block->attachments() : [];
        $html = '<div class="attachments">';
        for ($i = 1; $i <= $numAttachments; $i++) {
            if ($attachment = current($attachments)) {
                next($attachments);
            } else {
                $attachment = null;
            }
            $html .= $this->attachmentForm($view, $attachment, $site);
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a form for adding/editing a block attachment.
     *
     * @param PhpRenderer $view
     * @param SiteBlockAttachmentRepresentation|null $block
     * @param SiteRepresentation $site
     * @return string
     */
    public function attachmentForm(PhpRenderer $view,
        SiteBlockAttachmentRepresentation $attachment = null,
        SiteRepresentation $site
    ) {

        $itemId = null;
        $mediaId = null;
        $caption = null;
        $sidebarContentUrl = $view->url('admin/default',
            ['controller' => 'item', 'action' => 'sidebar-select'],
            ['query' => $site->itemPool()]
        );
        $title = '';
        $selectButton = '<button class="item-select" data-sidebar-content-url="' . $sidebarContentUrl . '">Select Item</button>';
        if ($attachment) {
            $item = $attachment->item();
            $itemId = $item->id();
            if ($attachment->media()) {
                $mediaId = $attachment->media()->id();
            }
            if ($item->primaryMedia()) {
                $thumbnail = '<img src="' . $item->primaryMedia()->thumbnailUrl('square') . '" title="' .  $item->primaryMedia()->displayTitle() . '" alt="' . $item->primaryMedia()->mediaType() . ' thumbnail">';
                $title = $thumbnail;
            }
            $title = $title . $item->displayTitle();
            $caption = $attachment->caption();
        }
        $html = '
<div class="attachment">
    <div class="field">
        <div class="field-meta">
            <label>Item</label>
        </div>
        <div class="inputs">
            <div class="item-title">' . $title . '</div>' . $selectButton .'
        </div>
    </div>
    <div class="field">
        <div class="field-meta">
            <label>Caption</label>
        </div>
        <div class="inputs">
            <textarea class="caption" name="o:block[__blockIndex__][o:attachment][__attachmentIndex__][o:caption]">' . $caption . '</textarea>
        </div>
    </div>
    <input type="hidden" class="item" name="o:block[__blockIndex__][o:attachment][__attachmentIndex__][o:item][o:id]" value="' . $itemId . '">
    <input type="hidden" class="media" name="o:block[__blockIndex__][o:attachment][__attachmentIndex__][o:media][o:id]" value="' . $mediaId . '">
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
