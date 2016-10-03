<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A vocabulary.
 *
 * Vocabularies are defined sets of classes and properties.
 *
 * @Entity
 */
class Vocabulary extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="vocabularies")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @Column(unique=true, length=190)
     */
    protected $namespaceUri;

    /**
     * @Column(unique=true, length=190)
     */
    protected $prefix;

    /**
     * @Column
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @OneToMany(
     *     targetEntity="ResourceClass",
     *     mappedBy="vocabulary",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     * @OrderBy({"label" = "ASC"})
     */
    protected $resourceClasses;

    /**
     * @OneToMany(
     *     targetEntity="Property",
     *     mappedBy="vocabulary",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     * @OrderBy({"label" = "ASC"})
     */
    protected $properties;

    public function __construct()
    {
        $this->resourceClasses = new ArrayCollection;
        $this->properties = new ArrayCollection;
    }

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

    public function setNamespaceUri($namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    public function getNamespaceUri()
    {
        return $this->namespaceUri;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function getResourceClasses()
    {
        return $this->resourceClasses;
    }

    public function getProperties()
    {
        return $this->properties;
    }
}
