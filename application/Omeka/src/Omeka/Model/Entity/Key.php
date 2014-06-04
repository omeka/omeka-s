<?php
namespace Omeka\Model\Entity;

use Zend\Crypt\Password\Bcrypt;
use Zend\Math\Rand;

/**
 * @Entity
 */
class Key extends AbstractEntity
{
    /**
     * The length of the key identity and credential.
     *
     * If this changes the identity annotation must change as well.
     */
    const STRING_LENGTH = 32;

    /**
     * The allowed character list for the key identity and credential.
     */
    const STRING_CHARLIST = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    /**
     * The key identity
     *
     * @Id
     * @Column(length=32)
     */
    protected $id;

    /**
     * The hashed key credential
     *
     * @Column(length=60)
     */
    protected $credentialHash;

    /**
     * The associated user
     *
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(nullable=false)
     */
    protected $user;

    public function setId()
    {
        $this->id = $this->getString();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the key credential, storing it hashed.
     *
     * @return string The unencrypted credential. This will be the only
     * opportunity to obtain the unencrypted credential.
     */
    public function setCredential()
    {
        $credential = $this->getString();
        $bcrypt = new Bcrypt;
        $this->credentialHash = $bcrypt->create($credential);
        return $credential;
    }

    /**
     * Verify a key credential.
     *
     * @param string The credential to verify
     */
    public function verifyCredential($credential)
    {
        $bcrypt = new Bcrypt;
        return $bcrypt->verify($credential, $this->credentialHash);
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    protected function getString()
    {
        return Rand::getString(self::STRING_LENGTH, self::STRING_CHARLIST);
    }
}
