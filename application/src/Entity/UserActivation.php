<?php
namespace Omeka\Entity;

use DateTime;
use Omeka\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class UserActivation extends AbstractEntity
{
    /**
     * @Id
     * @Column(length=32)
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="User")
     */
    protected $user;

    /**
     * @Column(type="datetime")
     */
    protected $created;


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

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }
}
