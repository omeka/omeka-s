<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(
 *             name="block_position",
 *             columns={"block_id", "position"}
 *         )
 *     }
 * )
 */
class SiteBlockAttachment extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="SitePageBlock", inversedBy="attachments")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $block;

    /**
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="siteBlockAttachments")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $item;

    /**
     * @ORM\ManyToOne(targetEntity="Media")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $media;

    /**
     * @ORM\Column(type="text")
     */
    protected $caption;

    /**
     * @ORM\Column(type="integer")
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
