<?php
namespace EADImport\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Job;
use Omeka\Entity\Site;

/**
 * @Entity
 */
class EADImportEntity extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Site")
     * @JoinColumn(nullable=false)
     */
    protected $site;

    /**
     * @Column(type="integer")
     */
    protected $entity_id;

    /**
     * API resource type (not neccesarily a Resource class)
     * @Column(type="string")
     */
    protected $resource_type;

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

    public function getEntityId()
    {
        return $this->entity_id;
    }

    public function setEntityId($entityId)
    {
        $this->entity_id = $entityId;
    }

    public function setResourceType($resourceType)
    {
        $this->resource_type = $resourceType;
    }

    public function getResourceType()
    {
        return $this->resource_type;
    }
}
