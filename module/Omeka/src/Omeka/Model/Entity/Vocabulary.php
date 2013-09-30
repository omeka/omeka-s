<?php
namespace Omeka\Model\Entity;

/**
 * A vocabulary.
 * 
 * Vocabularies are defined sets of classes and properties.
 * 
 * @Entity
 */
class Vocabulary extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="User") */
    protected $owner;
    
    /** @Column(unique=true) */
    protected $namespaceUri;
    
    /** @Column */
    protected $label;
    
    /** @Column(type="text", nullable=true) */
    protected $comment;
    
    public function getId()
    {
        return $this->id;
    }

    public function setData(array $data)
    {
    }

    public function toArray()
    {
    }
}
