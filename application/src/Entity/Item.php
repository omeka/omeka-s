<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Item extends Resource
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Media")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $primaryMedia;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Media",
     *     mappedBy="item",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"},
     *     indexBy="id"
     * )
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $media;

    /**
     * @ORM\OneToMany(targetEntity="SiteBlockAttachment", mappedBy="item")
     */
    protected $siteBlockAttachments;

    /**
     * @ORM\ManyToMany(targetEntity="ItemSet", inversedBy="items", indexBy="id")
     * @ORM\JoinTable(name="item_item_set")
     */
    protected $itemSets;

    /**
     * @ORM\ManyToMany(targetEntity="Site", inversedBy="items", indexBy="id")
     * @ORM\JoinTable(name="item_site")
     */
    protected $sites;

    public function __construct()
    {
        parent::__construct();
        $this->media = new ArrayCollection;
        $this->siteBlockAttachments = new ArrayCollection;
        $this->itemSets = new ArrayCollection;
        $this->sites = new ArrayCollection;
    }

    public function getResourceName()
    {
        return 'items';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPrimaryMedia(Media $primaryMedia = null)
    {
        $this->primaryMedia = $primaryMedia;
    }

    public function getPrimaryMedia()
    {
        return $this->primaryMedia;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function getSiteBlockAttachments()
    {
        return $this->siteBlockAttachments;
    }

    public function getItemSets()
    {
        return $this->itemSets;
    }

    public function getSites()
    {
        return $this->sites;
    }
}
