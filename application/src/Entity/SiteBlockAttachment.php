<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *     indexes={
 *         @Index(
 *             name="block_position",
 *             columns={"block_id", "position"}
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
     * @ManyToOne(targetEntity="Item", inversedBy="siteBlockAttachments")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $item;

    /**
     * @ManyToOne(targetEntity="Media")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $media;

    /**
     * @Column(type="text")
     */
    protected $caption;

    /**
     * @Column(type="integer")
     */
    protected $position;

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

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setItem(Item $item = null)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setMedia(Media $media = null)
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setBlock(SitePageBlock $block)
    {
        $this->block = $block;
    }

    public function getBlock()
    {
        return $this->block;
    }
}
