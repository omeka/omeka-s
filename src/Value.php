<?php
/**
 * @Entity
 */
class Value
{
    const TYPE_LITERAL = 'literal';
    
    const TYPE_RESOURCE = 'resource';
    
    const TYPE_URI = 'uri';
    
    /** @Id @Column(type="integer") @GeneratedValue
     */
    protected $id;
    
    /** @ManyToOne(targetEntity="Resource") @JoinColumn(nullable=false) */
    protected $resource;
    
    /** @ManyToOne(targetEntity="Property") @JoinColumn(nullable=false) */
    protected $property;
    
    /** @Column */
    protected $type;
    
    /** @Column(type="text", nullable=true) */
    protected $value;
    
    /** @Column(type="text", nullable=true) */
    protected $valueTransformed;
    
    /** @Column(nullable=true) */
    protected $lang;
    
    /** @Column(type="boolean") */
    protected $html;
    
    /** @ManyToOne(targetEntity="Resource") */
    protected $valueResource;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setType($type)
    {
        if (!in_array($type, array(
            self::TYPE_LITERAL, self::TYPE_RESOURCE, self::TYPE_URI
        ))) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->type = $type;
    }
}
