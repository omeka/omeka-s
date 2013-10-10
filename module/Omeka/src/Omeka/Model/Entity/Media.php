<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Media extends Resource
{
    /** @Id @Column(type="integer") */
    protected $id;

    /** @ManyToOne(targetEntity="Item") @JoinColumn(nullable=false) */
    protected $item;

    /** @Column */
    protected $type;
    
    /** @Column(type="text", nullable=true) */
    protected $data;

    /** @OneToOne(targetEntity="File") */
    private $file;

    public function getId()
    {
        return $this->id;
    }

    public function setItem($item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
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

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }
}
