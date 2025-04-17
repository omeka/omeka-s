<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *   indexes={
 *     @ORM\Index(columns={"title", "text"}, flags={"fulltext"})
 *   }
 * )
 */
class FulltextSearch
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=190)
     */
    protected $resource;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
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
