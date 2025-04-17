<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             columns={"site_id", "item_set_id"}
 *         )
 *     },
 *     indexes={
 *         @ORM\Index(
 *             name="position",
 *             columns={"position"}
 *         )
 *     }
 * )
 */
class SiteItemSet extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="siteItemSets")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="ItemSet", inversedBy="siteItemSets")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $itemSet;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $position;

    public function getId()
    {
        return $this->id;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setItemSet(ItemSet $itemSet)
    {
        $this->itemSet = $itemSet;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }

    public function setPosition($position)
    {
        $this->position = (int) $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
