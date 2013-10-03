<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Media extends Resource
{
    /** @Id @Column(type="integer") */
    protected $id;
    
    /** @ManyToOne(targetEntity="Item") @JoinColumn(nullable=false) */
    protected $item;
    
    /** @Column */
    protected $type;
    
    /** @Column(type="text", nullable=true) */
    protected $data;
    
    /** @OneToOne(targetEntity="File") */
    private $file;
    
    public function getId()
    {
        return $this->id;
    }
}
