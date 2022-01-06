<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class ResourceTemplate extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(unique=true, length=190)
     */
    protected $label;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="resourceTemplates")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="ResourceClass")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $resourceClass;

    /**
     * @ManyToOne(targetEntity="Property")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $titleProperty;

    /**
     * @ManyToOne(targetEntity="Property")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $descriptionProperty;

    /**
     * @OneToMany(
     *     targetEntity="ResourceTemplateProperty",
     *     mappedBy="resourceTemplate",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"},
     *     indexBy="property_id"
     * )
     * @OrderBy({"position" = "ASC"})
     */
    protected $resourceTemplateProperties;

    /**
     * @OneToMany(
     *     targetEntity="Resource",
     *     mappedBy="resourceTemplate",
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $resources;

    public function __construct()
    {
        $this->resourceTemplateProperties = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setResourceClass(ResourceClass $resourceClass = null)
    {
        $this->resourceClass = $resourceClass;
        return $this;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setTitleProperty(Property $titleProperty = null)
    {
        $this->titleProperty = $titleProperty;
    }

    public function getTitleProperty()
    {
        return $this->titleProperty;
    }

    public function setDescriptionProperty(Property $descriptionProperty = null)
    {
        $this->descriptionProperty = $descriptionProperty;
    }

    public function getDescriptionProperty()
    {
        return $this->descriptionProperty;
    }

    public function getResourceTemplateProperties()
    {
        return $this->resourceTemplateProperties;
    }
}
