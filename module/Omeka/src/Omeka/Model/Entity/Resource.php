<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * A resource, representing the subject in an RDF triple.
 * 
 * Note that the discriminator map is loaded dynamically.
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="resource_type", type="string")
 *
 * @see \Omeka\Db\Event\Listener\ResourceDiscriminatorMap
 */
class Resource implements EntityInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="ResourceClass")
     * @JoinColumn(nullable=false)
     */
    protected $resourceClass;

    /**
     * @OneToMany(targetEntity="SiteResource", mappedBy="resource")
     */
    protected $sites;

    public function __construct()
    {
        $this->sites = new ArrayCollection;
    }

    /**
     * All resources must belong to a class. If one is not set prior to persist, 
     * set it to the default class of this resource type.
     * 
     * @PrePersist
     */
    public function setDefaultResourceClass(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        if (null === $this->resourceClass) {
            $resourceClass = $entityManager->getRepository('Omeka\Model\Entity\ResourceClass')
                ->findOneBy(array(
                    'resourceType' => get_called_class(),
                    'isDefault' => true)
                );
            $this->resourceClass = $resourceClass;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setResourceClass($resourceClass)
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function getSites()
    {
        return $this->sites;
    }
}
