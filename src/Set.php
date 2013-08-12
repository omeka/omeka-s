<?php
/**
 * @Entity @Table(name="`set`")
 */
class Set extends Resource
{
    /** @Id @Column(type="integer") */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}
