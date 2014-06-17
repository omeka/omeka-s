<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A property, representing the predicate in an RDF triple.
 * 
 * Properties define relationships between resources and their values.
 * 
 * @Entity
 * @Table(
 *     options={"collate"="utf8_bin"},
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             name="vocabulary_local_name",
 *             columns={"vocabulary_id", "local_name"}
 *         )
 *     }
 * )
 *
 * @todo Once the following Doctrine DBAL bug is resolved, move the utf8_bin
 * collation to the localName column, using options={"collation"="utf8_bin"}.
 * That particular collation is needed so unique constraints are case sensitive.
 * http://www.doctrine-project.org/jira/browse/DBAL-647 
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
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="Vocabulary", inversedBy="properties")
     */
    protected $vocabulary;

    /**
     * @Column
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
        if ($vocabulary instanceof Vocabulary) {
            $vocabulary->getProperties()->add($this);
        }
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
