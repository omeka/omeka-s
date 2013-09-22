<?php
namespace Omeka\Model\Entity;

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
