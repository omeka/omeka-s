<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Media extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column
     */
    protected $type;
    
    /**
     * @Column(type="json_array", nullable=true)
     */
    protected $data;

    /**
     * @Column(type="boolean")
     */
    protected $isPublic = false;

    /**
     * @ManyToOne(targetEntity="Item", inversedBy="media")
     * @JoinColumn(nullable=false)
     */
    protected $item;

    /**
     * @OneToOne(targetEntity="File")
     */
    protected $file;

    public function getResourceName()
    {
        return 'media';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setItem(Item $item = null)
    {
        $this->synchronizeOneToMany($item, 'item', 'getMedia');
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setFile(File $file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }
}
