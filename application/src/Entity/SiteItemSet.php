<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"site_id", "item_set_id"}
 *         )
 *     },
 *     indexes={
 *         @Index(
 *             name="position",
 *             columns={"position"}
 *         )
 *     }
 * )
 */
class SiteItemSet extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Site", inversedBy="siteItemSets")
     * @JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $site;

    /**
     * @ManyToOne(targetEntity="ItemSet", inversedBy="siteItemSets")
     * @JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $itemSet;

    /**
     * @Column(type="integer", nullable=true)
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
