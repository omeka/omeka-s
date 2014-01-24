<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class User implements EntityInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    protected $username;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    protected $email;

    /**
     * @Column(type="string", length=255)
     */
    protected $name;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getCreated()
    {
        return $this->created;
    }
}
