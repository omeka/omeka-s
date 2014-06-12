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
     * @Column(type="text", nullable=true)
     */
    protected $data;

    /**
     * @ManyToOne(targetEntity="Item")
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

    public function setItem(Item $item)
    {
        $this->item = $item;
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
