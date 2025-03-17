<?php
namespace Collecting\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\User;

/**
 * @Entity
 */
class CollectingUser extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $user;

    /**
     * @OneToMany(
     *     targetEntity="CollectingItem",
     *     mappedBy="user",
     *     orphanRemoval=true,
     *     cascade={"all"}
     * )
     */
    protected $collectingItems;

    public function __construct()
    {
        $this->collectingItems = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCollectingItems()
    {
        return $this->collectingItems;
    }
}
