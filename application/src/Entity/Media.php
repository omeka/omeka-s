<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *     indexes={
 *         @Index(
 *             name="item_position",
 *             columns={"item_id", "position"}
 *         )
 *     }
 * )
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
    protected $ingester;

    /**
     * @Column
     */
    protected $renderer;

    /**
     * @Column(type="json_array", nullable=true)
     */
    protected $data;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $source;

    /**
     * @Column(nullable=true)
     */
    protected $mediaType;

    /**
     * @Column(nullable=true, unique=true, length=190)
     */
    protected $storageId;

    /**
     * @Column(nullable=true)
     */
    protected $extension;

    /**
     * @Column(nullable=true, type="string", length=64, options={"fixed" = true})
     */
    protected $sha256;

    /**
     * @Column(type="bigint", nullable=true)
     */
    protected $size;

    /**
     * @Column(type="boolean")
     */
    protected $hasOriginal = false;

    /**
     * @Column(type="boolean")
     */
    protected $hasThumbnails = false;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $position;

    /**
     * @ManyToOne(targetEntity="Item", inversedBy="media")
     * @JoinColumn(nullable=false)
     */
    protected $item;

    /**
     * @Column(nullable=true, length=190)
     */
    protected $lang;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $altText;

    public function getResourceName()
    {
        return 'media';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIngester($ingester)
    {
        $this->ingester = $ingester;
        return $this;
    }

    public function getIngester()
    {
        return $this->ingester;
    }

    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
        return $this;
    }

    public function getRenderer()
    {
        return $this->renderer;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    public function getSource()
    {
        return $this->source;
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

    public function setHasOriginal($hasOriginal)
    {
        $this->hasOriginal = (bool) $hasOriginal;
        return $this;
    }

    public function hasOriginal()
    {
        return (bool) $this->hasOriginal;
    }

    public function setHasThumbnails($hasThumbnails)
    {
        $this->hasThumbnails = (bool) $hasThumbnails;
        return $this;
    }

    public function hasThumbnails()
    {
        return (bool) $this->hasThumbnails;
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

    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;
        return $this;
    }

    public function getSha256()
    {
        return $this->sha256;
    }

    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getFilename()
    {
        $filename = $this->storageId;
        if ($filename !== null && $this->extension !== null) {
            $filename .= '.' . $this->extension;
        }
        return $filename;
    }

    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setItem(Item $item = null)
    {
        $this->item = $item;
        return $this;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setAltText($altText)
    {
        $this->altText = $altText;
    }

    public function getAltText()
    {
        return $this->altText;
    }
}
