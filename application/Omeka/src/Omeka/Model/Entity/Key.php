<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Key extends AbstractEntity
{
    /**
     * @Id
     * @Column
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User")
     */
    protected $user;

    public function getId()
    {
        return $this->id;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
