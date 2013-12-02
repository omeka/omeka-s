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
abstract class Resource implements EntityInterface
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
     * Get the fully qualified class name of the entity API adapter.
     *
     * This can be used when the entity is known but the corresponding adapter
     * is not. Primarily used when extracting children of this class (Item,
     * Media, ItemSet, etc.) to an array when the adapter is unknown.
     *
     * @see \Omeka\Api\Adapter\Entity\AbstractEntityAdapter::extract()
     * @return string
     */
    abstract public function getAdapterClass();

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
