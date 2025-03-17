<?php
namespace Collecting\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;
use Omeka\Entity\User;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class CollectingItem extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(
     *     targetEntity="Omeka\Entity\Item"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $item;

    /**
     * @ManyToOne(
     *     targetEntity="CollectingForm",
     *     inversedBy="items"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $form;

    /**
     * @ManyToOne(
     *     targetEntity="CollectingUser",
     *     inversedBy="items",
     *     cascade={"persist"}
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $collectingUser;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $reviewer;

    /**
     * @Column(nullable=true)
     */
    protected $userName;

    /**
     * @Column(nullable=true)
     */
    protected $userEmail;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $anon;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $reviewed = false;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * @OneToMany(
     *     targetEntity="CollectingInput",
     *     mappedBy="collectingItem",
     *     orphanRemoval=true,
     *     cascade={"all"}
     * )
     */
    protected $inputs;

    public function __construct()
    {
        $this->inputs = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setItem(Item $item = null)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setForm(CollectingForm $form)
    {
        $this->form = $form;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function setCollectingUser(CollectingUser $collectingUser)
    {
        $this->collectingUser = $collectingUser;
    }

    public function getCollectingUser()
    {
        return $this->collectingUser;
    }

    public function setReviewer(User $reviewer = null)
    {
        $this->reviewer = $reviewer;
    }

    public function getReviewer()
    {
        return $this->reviewer;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
    }

    public function getUserEmail()
    {
        return $this->userEmail;
    }

    public function setAnon($anon)
    {
        $this->anon = isset($anon) ? (bool) $anon : null;
    }

    public function getAnon()
    {
        return $this->anon;
    }

    public function setReviewed($reviewed)
    {
        $this->reviewed = (bool) $reviewed;
    }

    public function getReviewed()
    {
        return $this->reviewed;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setModified(DateTime $dateTime)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function getInputs()
    {
        return $this->inputs;
    }

    public function getSite()
    {
        return $this->getForm()->getSite();
    }

    /**
     * Proxy for self::getCollectingUser()
     *
     * Needed for the OwnsEntityAssertion ACL assertion.
     */
    public function getOwner()
    {
        return $this->collectingUser;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }

    /**
     * @PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $this->modified = new DateTime('now');
    }
}
