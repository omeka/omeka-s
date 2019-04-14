<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class Asset extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @Column
     */
    protected $name;

    /**
     * @Column
     */
    protected $mediaType;

    /**
     * @Column(length=190, unique=true)
     */
    protected $storageId;

    /**
     * @Column(nullable=true)
     */
    protected $extension;

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;
        return $this;
    }

    public function getMediaType()
    {
        return $this->mediaType;
    }

    public function getFilename()
    {
        $filename = $this->storageId;
        if ($filename !== null && $this->extension !== null) {
            $filename .= '.' . $this->extension;
        }
        return $filename;
    }

    public function setStorageId($storageId)
    {
        $this->storageId = $storageId;
        return $this;
    }

    public function getStorageId()
    {
        return $this->storageId;
    }

    public function setExtension($extension)
    {
        $this->extension = $extension;
        return $this;
    }

    public function getExtension()
    {
        return $this->extension;
    }
}
