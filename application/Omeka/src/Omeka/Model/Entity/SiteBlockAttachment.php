<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 * @Table(
 *     indexes={
 *         @Index(
 *             name="block_order",
 *             columns={"block_id", "order"}
 *         )
 *     }
 * )
 */
class SiteBlockAttachment extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="SitePageBlock", inversedBy="attachments")
     * @JoinColumn(nullable=false)
     */
    protected $block;

    /**
     * @ManyToOne(targetEntity="Item")
     * @JoinColumn(nullable=false)
     */
    protected $item;

    /**
     * @ManyToOne(targetEntity="Media")
     */
    protected $media;

    /**
     * @Column(type="text")
     */
    protected $caption;

    /**
     * @Column(type="integer")
     */
    protected $order;

    public function getId()
    {
        return $this->id;
    }

    public function setCaption($caption)
    {
        $this->caption = $caption;
    }

    public function getCaption()
    {
        return $this->caption;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $item;
    }

    public function setMedia(Media $media = null)
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $media;
    }

    public function setBlock(SitePageBlock $block)
    {
        $this->synchronizeOneToMany($block, 'block', 'getAttachments');
    }

    public function getBlock()
    {
        return $this->block;
    }
}
