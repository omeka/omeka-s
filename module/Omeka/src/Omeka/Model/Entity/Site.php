<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Site extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @OneToMany(targetEntity="SiteResource", mappedBy="site") */
    protected $siteResources;

    public function getId()
    {
        return $this->id;
    }
}
