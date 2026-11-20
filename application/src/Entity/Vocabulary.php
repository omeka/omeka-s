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
    /** The longest local name a class or property can have, as their local_name columns allow. */
    const LOCAL_NAME_MAX_LENGTH = 190;
    /** The longest label a class or property can have, as their label columns allow. */
    const LABEL_MAX_LENGTH = 255;

    /**
     * How a vocabulary's file was read, stored so an update reads the next
     * version the same way.
     */
    const IMPORT_OPTIONS = ['format', 'lang', 'label_property', 'comment_property'];

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
     * The import options the vocabulary's file was read with.
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

    /**
     * Get the forms of a namespace URI that name the same vocabulary: itself,
     * then ending in "#", "/", neither, and "/#", each with http and https.
     * For example, http://schema.org/ and https://schema.org/ are forms of
     * one namespace URI.
     *
     * @param string $namespaceUri
     * @return string[]
     */
    public static function getNamespaceUriForms($namespaceUri)
    {
        $namespaceUri = (string) $namespaceUri;
        $base = rtrim($namespaceUri, '#/');
        $forms = [$namespaceUri, $base . '#', $base . '/', $base, $base . '/#'];
        $otherScheme = [];
        foreach ($forms as $form) {
            if (0 === stripos($form, 'http://')) {
                $otherScheme[] = 'https://' . substr($form, 7);
            } elseif (0 === stripos($form, 'https://')) {
                $otherScheme[] = 'http://' . substr($form, 8);
            }
        }
        return array_values(array_unique(array_merge($forms, $otherScheme)));
    }
}
