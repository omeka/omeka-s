<?php
namespace Omeka\Model;

/**
 * @Entity
 */
class Media extends Resource
{
    /** @Id @Column(type="integer") */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}
