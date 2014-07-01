<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A resource, representing the subject in an RDF triple.
 * 
 * Note that the discriminator map is loaded dynamically.
 * 
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="resource_type", type="string")
 *
 * @see \Omeka\Db\Event\Listener\ResourceDiscriminatorMap
 */
abstract class Resource extends AbstractEntity
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
     * @OneToMany(
     *     targetEntity="Value",
     *     mappedBy="resource",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $values;

    public function __construct()
    {
        $this->values = new ArrayCollection;
    }

    /**
     * Get the resource name of the corresponding entity API adapter.
     *
     * This can be used when the entity is known but the corresponding adapter
     * is not. Primarily used when extracting children of this class (Item,
     * Media, ItemSet, etc.) to an array when the adapter is unknown.
     *
     * @return string
     */
    abstract public function getResourceName();

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setResourceClass(ResourceClass $resourceClass)
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function getValues()
    {
        return $this->values;
    }

    /**
     * Add a value to this resource.
     *
     * @param Value $value
     */
    public function addValue(Value $value)
    {
        $value->setResource($this);
    }

    /**
     * Remove a value from this resource.
     *
     * @param Value $value
     * @return bool
     */
    public function removeValue(Value $value)
    {
        $value->setResource(null);
    }
}
