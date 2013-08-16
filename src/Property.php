<?php
/**
 * A property, representing the predicate in an RDF triple.
 * 
 * Properties define relationships between resources and their values.
 * 
 * @Entity
 */
class Property
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="Vocabulary") */
    protected $vocabulary;
    
    /** @ManyToOne(targetEntity="Property", inversedBy="subproperties") */
    protected $superproperty;
    
    /** @OneToMany(targetEntity="Property", mappedBy="superproperty") */
    protected $subproperties;
    
    /** @OneToMany(targetEntity="ResourceClassProperty", mappedBy="property") */
    protected $resourceClasses;
    
    /** @Column(nullable=true) */
    protected $localName;
    
    /** @Column */
    protected $label;
    
    /** @Column(type="text", nullable=true) */
    protected $comment;
    
    public function __construct()
    {
        $this->subproperties = new \Doctrine\Common\Collections\ArrayCollection();
        $this->resourceClasses = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function getId()
    {
        return $this->id;
    }
}
