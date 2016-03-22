<?php
namespace Omeka\Entity;

use DateInterval;
use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class PasswordCreation extends AbstractEntity
{
    /**
     * @Id
     * @Column(options={"collation"="utf8mb4_bin"}, length=32)
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * Whether to activate the user after setting a new password.
     *
     * @Column(type="boolean")
     */
    protected $activate = true;

    public function setId()
    {
        // Reuse key generation from ApiKey entity.
        $apiKey = new ApiKey;
        $apiKey->setId();
        $this->id = $apiKey->getId();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setActivate($activate)
    {
        $this->activate = (bool) $activate;
    }

    public function activate()
    {
        return (bool) $this->activate;
    }

    /**
     * Expires two weeks after creation
     *
     * @return DateTime
     */
    public function getExpiration()
    {
        return $this->getCreated()->add(new DateInterval('P2W'));
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }
}
