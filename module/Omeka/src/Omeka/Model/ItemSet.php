<?php
namespace Omeka\Model;

/**
 * @Entity
 */
class ItemSet extends Resource
{
    /** @Id @Column(type="integer") */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}
