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
     * @Column
     */
    protected $type;

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
     * @param string $type
     */
    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
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
