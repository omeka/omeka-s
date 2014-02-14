<?php
namespace Omeka\Model\Entity;

use Zend\Crypt\Password\Bcrypt;

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
     * @Column(unique=true)
     */
    protected $username;

    /**
     * @Column(type="string", length=60, nullable=true)
     */
    protected $passwordHash;
    
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
}
