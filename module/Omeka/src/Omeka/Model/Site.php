<?php
namespace Omeka\Model;

/**
 * @Entity
 */
class Site
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @OneToMany(targetEntity="SiteResource", mappedBy="site") */
    protected $sites;
    
    public function getId()
    {
        return $this->id;
    }
}
