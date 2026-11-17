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
    /** Regex for a valid vocabulary prefix. */
    const PREFIX_REGEX = '/^[a-z0-9_-]+$/i';
    /** Regex for a valid JSON-LD term in prefix:localName form. */
    const TERM_REGEX = '/^[a-z0-9_-]+:[a-z0-9_-]+$/i';
    /** The longest local name a class or property can have, in characters. */
    const LOCAL_NAME_MAX_LENGTH = 190;
    /** The longest label a class or property can have, in characters. */
    const LABEL_MAX_LENGTH = 255;

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
     * The reading options the vocabulary's file was read with.
     *
     * @Column(type="json", nullable=true)
     */
    protected $importOptions;

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

    public function setOwner(?User $owner = null)
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

    public function setImportOptions(?array $importOptions = null)
    {
        $this->importOptions = $importOptions;
    }

    public function getImportOptions()
    {
        return $this->importOptions;
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
