<?php
namespace EADImport\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Job;
use Omeka\Entity\Site;

/**
 * @Entity
 */
class EADImportImport extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Site")
     * @JoinColumn(nullable=false)
     */
    protected $site;

    /**
     * @Column(type="string")
     */
    protected $name;

    /**
     * @Column(type="string")
     */
    protected $resource_type;

    /**
     * @Column(type="json")
     * @JoinColumn(nullable=false)
     */
    protected $mapping;

    public function getId()
    {
        return $this->id;
    }

    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setResourceType($resourceType)
    {
        $this->resource_type = $resourceType;
    }

    public function getResourceType()
    {
        return $this->resource_type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function setMapping($mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }
}
