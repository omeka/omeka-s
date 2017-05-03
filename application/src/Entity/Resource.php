<?php
namespace Omeka\Entity;

use DateTime;
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
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="ResourceClass", inversedBy="resources")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $resourceClass;

    /**
     * @ManyToOne(targetEntity="ResourceTemplate")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $resourceTemplate;

    /**
     * @Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * @OneToMany(
     *     targetEntity="Value",
     *     mappedBy="resource",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"}
     * )
     * @OrderBy({"id" = "ASC"})
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

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setResourceClass(ResourceClass $resourceClass = null)
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setResourceTemplate(ResourceTemplate $resourceTemplate = null)
    {
        $this->resourceTemplate = $resourceTemplate;
    }

    public function getResourceTemplate()
    {
        return $this->resourceTemplate;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setModified(DateTime $dateTime)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function getValues()
    {
        return $this->values;
    }
}
