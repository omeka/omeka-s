<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *   indexes={
 *     @Index(columns={"title", "text"}, flags={"fulltext"})
 *   }
 * )
 */
class FulltextSearch
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Id
     * @Column(type="string", length=190)
     */
    protected $resource;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $title;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @param int $id
     * @param string $resource
     */
    public function __construct($id, $resource)
    {
        $this->id = $id;
        $this->resource = $resource;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function getIsPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }
}
