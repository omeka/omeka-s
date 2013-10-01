<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class User extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(unique=true) */
    protected $username;
    
    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
