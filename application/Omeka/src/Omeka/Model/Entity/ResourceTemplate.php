<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Model\Entity\ResourceTemplateProperty;
use Omeka\Model\Entity\User;

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
     * @Column
     */
    protected $label;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="resourceTemplates")
     */
    protected $owner;

    /**
     * @OneToMany(
     *     targetEntity="ResourceTemplateProperty",
     *     mappedBy="resourceTemplate",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $resourceTemplateProperties;

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
        $this->synchronizeOneToMany($owner, 'owner', 'getResourceTemplates');
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getResourceTemplateProperties()
    {
        return $this->resourceTemplateProperties;
    }

    /**
     * Add a property assignment to this set.
     *
     * @param ResourceTemplateProperty $resourceTemplateProperty
     */
    public function addResourceTemplateProperty(ResourceTemplateProperty $resourceTemplateProperty)
    {
        $resourceTemplateProperty->setResourceTemplate($this);
    }

    /**
     * Remove a property assignment from this set.
     *
     * @param ResourceTemplateProperty $resourceTemplateProperty
     * @return bool
     */
    public function removeResourceTemplateProperty(ResourceTemplateProperty $resourceTemplateProperty)
    {
        $resourceTemplateProperty->setResourceTemplate(null);
    }
}
