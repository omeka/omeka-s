<?php
namespace Omeka\Model;

/**
 * @Entity
 */
class User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}
