<?php
namespace Omeka\Model\Entity;

/**
 * A resource, representing the subject in an RDF triple.
 * 
 * Note that the discriminator map is loaded dynamically.
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="resource_type", type="string")
 */
class Resource extends AbstractEntity
{
    const TYPE_ITEM_SET = 'ItemSet';
    
    const TYPE_ITEM = 'Item';
    
    const TYPE_MEDIA = 'Media';
    
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="User") */
    protected $owner;
    
    /** @ManyToOne(targetEntity="ResourceClass") @JoinColumn(nullable=false) */
    protected $resourceClass;
    
    /** @OneToMany(targetEntity="SiteResource", mappedBy="resource") */
    protected $sites;
    
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
            $resourceClass = $em->getRepository('Omeka\\Model\\ResourceClass')
                ->findOneBy(array('resourceType' => get_called_class(), 'isDefault' => true));
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
