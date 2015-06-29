<?php
namespace Omeka\Entity;

use DateTime;
use Zend\Crypt\Password\Bcrypt;
use Zend\Math\Rand;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class ApiKey extends AbstractEntity
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
     * @Column
     */
    protected $label;

    /**
     * The hashed key credential
     *
     * @Column(length=60)
     */
    protected $credentialHash;

    /**
     * @Column(type="ip_address", nullable=true)
     */
    protected $lastIp;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $lastAccessed;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * The associated user
     *
     * @ManyToOne(targetEntity="User", inversedBy="keys", fetch="EAGER")
     * @JoinColumn(nullable=false)
     */
    protected $owner;

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

    public function setId()
    {
        $this->id = $this->getString();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
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

    public function setLastIp($lastIp)
    {
        $this->lastIp = $lastIp;
    }

    public function getLastIp()
    {
        return $this->lastIp;
    }

    public function setLastAccessed(DateTime $lastAccessed)
    {
        $this->lastAccessed = $lastAccessed;
    }

    public function getLastAccessed()
    {
        return $this->lastAccessed;
    }

    public function setCreated(DateTime $created)
    {
        $this->created = $created;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    protected function getString()
    {
        return Rand::getString(self::STRING_LENGTH, self::STRING_CHARLIST, true);
    }
}
