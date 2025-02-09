<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A property, representing the predicate in an RDF triple.
 *
 * Properties define relationships between resources and their values.
 *
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"vocabulary_id", "local_name"}
 *         )
 *     }
 * )
 */
class Property extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="properties")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="Vocabulary", inversedBy="properties")
     * @JoinColumn(nullable=false)
     */
    protected $vocabulary;

    /**
     * @Column(options={"collation"="utf8mb4_bin"}, length=190)
     */
    protected $localName;

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
     *     targetEntity="Value",
     *     mappedBy="property",
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $values;

    public function __construct()
    {
        $this->values = new ArrayCollection;
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

    public function setVocabulary(Vocabulary $vocabulary = null)
    {
        $this->vocabulary = $vocabulary;
    }

    public function getVocabulary()
    {
        return $this->vocabulary;
    }

    public function setLocalName($localName)
    {
        $this->localName = $localName;
    }

    public function getLocalName()
    {
        return $this->localName;
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
