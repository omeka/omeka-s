<?php
/**
 * A resource type.
 * 
 * Essentially, resource types represent top-level classes that are specific to 
 * the Omeka data model.
 * 
 * @Entity
 * @HasLifecycleCallbacks
 */
class ResourceType
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @Column(unique=true) */
    protected $label;
    
    /** @Column(type="text", nullable=true) */
    protected $comment;
    
    /**
     * All resource types must have a corresponding default class.
     * 
     * @PostPersist
     */
    public function postPersistSetDefaultResourceClass($event)
    {
        $resourceClass = new ResourceClass;
        $resourceClass->setResourceType($this);
        $resourceClass->setDefault(true);
        $resourceClass->setLabel($this->label);
        $event->getEntityManager()->persist($resourceClass);
        $event->getEntityManager()->flush();
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    public function getComment()
    {
        return $this->comment;
    }
    
    public function setComment($comment)
    {
        $this->comment = $comment;
    }
}
