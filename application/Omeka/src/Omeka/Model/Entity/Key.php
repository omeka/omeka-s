<?php
namespace Omeka\Model\Entity;

use Zend\Crypt\Password\Bcrypt;
use Zend\Math\Rand;

/**
 * @Entity
 */
class Key extends AbstractEntity
{
    const STRING_LENGTH = 32;
    const STRING_CHARLIST = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    /**
     * @Id
     * @Column(length=60)
     */
    protected $credentialHash;

    /**
     * @Column
     */
    protected $identity;

    /**
     * @ManyToOne(targetEntity="User")
     */
    protected $user;

    /**
     * Set the key credential, storing it hashed.
     *
     * @return string The unencrypted key. This will be the only opportunity to
     * obtain the unencrypted key.
     */
    public function setCredential()
    {
        $credential = $this->getString();
        $bcrypt = new Bcrypt;
        $this->credentialHash = $bcrypt->create($credential);
        return $credential;
    }

    public function getId()
    {
        return $this->credentialHash;
    }

    /**
     * Set the key identity.
     */
    public function setIdentity()
    {
        $this->identity = $this->getString();
    }

    public function getIdentity()
    {
        return $this->identity;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * Verify a key credential.
     *
     * @param string The credential to verify
     */
    public function verifyCredential($credential)
    {
        if (is_null($this->credentialHash)) {
            return false;
        }
        $bcrypt = new Bcrypt;
        return $bcrypt->verify($credential, $this->credentialHash);
    }

    protected function getString()
    {
        return Rand::getString(self::STRING_LENGTH, self::STRING_CHARLIST);
    }
}
