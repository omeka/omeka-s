<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class Site extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="sites")
     */
    protected $owner;

    /**
     * @OneToMany(targetEntity="SiteItem", mappedBy="site")
     */
    protected $siteItems;

    public function __construct()
    {
        $this->siteItems = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner = null)
    {
        $this->synchronizeOneToMany($owner, 'owner', 'getSites');
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getSiteItems()
    {
        return $this->siteItems;
    }
}
