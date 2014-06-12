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
     * @OneToMany(targetEntity="SiteResource", mappedBy="site")
     */
    protected $siteResources;

    public function __construct()
    {
        $this->siteResources = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSiteResources()
    {
        return $this->siteResources;
    }
}
