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

    public function getSiteItems()
    {
        return $this->siteItems;
    }
}
