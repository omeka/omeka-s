<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ResourceTemplate extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(unique=true, length=190)
     */
    protected $label;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="resourceTemplates")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ORM\ManyToOne(targetEntity="ResourceClass")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $resourceClass;

    /**
     * @ORM\ManyToOne(targetEntity="Property")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $titleProperty;

    /**
     * @ORM\ManyToOne(targetEntity="Property")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $descriptionProperty;

    /**
     * @ORM\OneToMany(
     *     targetEntity="ResourceTemplateProperty",
     *     mappedBy="resourceTemplate",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"},
     *     indexBy="property_id"
     * )
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $resourceTemplateProperties;

    /**
     * @ORM\OneToMany(
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
    }

    public function getLabel()
    {
        return $this->label;
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
