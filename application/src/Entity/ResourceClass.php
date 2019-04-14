<?php
namespace Omeka\Entity;

/**
 * A resource class.
 *
 * Classes are logical groupings of resources that have specified ranges of
 * descriptive properties.
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
class ResourceClass extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="resourceClasses")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="Vocabulary", inversedBy="resourceClasses")
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
     *     targetEntity="Resource",
     *     mappedBy="resourceClass",
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $resources;

    public function getId()
    {
        return $this->id;
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

    public function setVocabulary(Vocabulary $vocabulary = null)
    {
        $this->vocabulary = $vocabulary;
        return $this;
    }

    public function getVocabulary()
    {
        return $this->vocabulary;
    }

    public function setLocalName($localName)
    {
        $this->localName = $localName;
        return $this;
    }

    public function getLocalName()
    {
        return $this->localName;
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

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }
}
