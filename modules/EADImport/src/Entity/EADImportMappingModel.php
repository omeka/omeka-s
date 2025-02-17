<?php
namespace EADImport\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class EADImportMappingModel extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string")
     * @JoinColumn(nullable=false)
     */
    protected $name;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="json")
     * @JoinColumn(nullable=false)
     */
    protected $mapping;

    public function getId()
    {
        return $this->id;
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

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }
}
