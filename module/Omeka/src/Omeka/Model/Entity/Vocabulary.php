<?php
namespace Omeka\Model\Entity;

/**
 * A vocabulary.
 * 
 * Vocabularies are defined sets of classes and properties.
 * 
 * @Entity
 */
class Vocabulary implements EntityInterface
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
     * @Column(unique=true)
     */
    protected $namespaceUri;

    /**
     * @Column
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $comment;

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

    public function setNamespaceUri($namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    public function getNamespaceUri()
    {
        return $this->namespaceUri;
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
}
