<?php
namespace Mapping\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;

/**
 * Defines the default state of an item's map.
 *
 * @Entity
 */
class Mapping extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $item;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $bounds;

    public function getId()
    {
        return $this->id;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setBounds($bounds)
    {
        $this->bounds = $bounds;
    }

    public function getBounds()
    {
        return $this->bounds;
    }
}
