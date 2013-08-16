<?php
/**
 * A resource, representing the subject in an RDF triple.
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="resource_type", type="string")
 * @DiscriminatorMap({"Item" = "Item", "Set" = "Set", "Media" = "Media"})
 */
class Resource
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** 
     * @ManyToOne(targetEntity="ResourceClass") @JoinColumn(nullable=false)
     */
    protected $resourceClass;
    
    /**
     * All resources must belong to a class. If one is not set prior to persist, 
     * set it to the default class of this resource type.
     * 
     * @PrePersist
     */
    public function prePersistSetResourceClass($event)
    {
        $em = $event->getEntityManager();
        if (null === $this->resourceClass) {
            $resourceType = $em->getRepository('ResourceType')->findOneByLabel(get_called_class());
            $resourceClass = $em->getRepository('ResourceClass')->findOneBy(array('resourceType' => $resourceType, 'default' => true));
            $this->resourceClass = $resourceClass;
        }
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getResourceClass()
    {
        return $this->resourceClass;
    }
    
    public function setResourceClass($resourceClass)
    {
        $this->resourceClass = $resourceClass;
    }
}
