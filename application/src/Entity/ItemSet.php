<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class ItemSet extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @ManyToMany(targetEntity="Item", mappedBy="itemSets", fetch="EXTRA_LAZY")
     * @JoinTable(name="item_item_set")
     */
    protected $items;

    public function __construct() {
        parent::__construct();
        $this->items = new ArrayCollection;
    }

    public function getResourceName()
    {
        return 'item_sets';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getItems()
    {
        return $this->items;
    }
}
