<?php
namespace Omeka\Model\Entity;

use DateTime;
use Zend\Crypt\Password\Bcrypt;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * @Entity @HasLifecycleCallbacks
 */
class User extends AbstractEntity implements RoleInterface
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

    /**
     * @Column(type="string", length=60, nullable=true)
     */
    protected $passwordHash;

    /**
     * @Column(type="string", length=255)
     */
    protected $role;


    /**
     * @PrePersist
     */
    public function prePersist()
    {
        if (null === $this->created) {
            // Set created datetime if not already set.
            $this->created = new DateTime;
        }
    }

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

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(DateTime $created)
    {
        $this->created = $created;
    }
    
    /**
     * Update the user's password, storing it hashed.
     *
     * @see Zend\Crypt\Password\Bcrypt
     * @param string $password Password to set.
     */
    public function setPassword($password)
    {
        $bcrypt = new Bcrypt;
        $this->passwordHash = $bcrypt->create($password);
    }

    /**
     * Verify that a given password is correct for the user.
     *
     * @param string $possiblePassword Password to check.
     * @return bool
     */
    public function verifyPassword($possiblePassword)
    {
        // If no password is set any is invalid
        if ($this->passwordHash === null) {
            return false;
        }

        $bcrypt = new Bcrypt;
        return $bcrypt->verify($possiblePassword, $this->passwordHash);
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleId()
    {
        return 'current_user';
    }
}
