<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(
 *     indexes={
 *         @Index(
 *             name="page_position",
 *             columns={"page_id", "position"}
 *         )
 *     }
 * )
 */
class SitePageBlock extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(length=80)
     */
    protected $layout;

    /**
     * @Column(type="json_array")
     */
    protected $data;

    /**
     * @Column(type="integer")
     */
    protected $position;

    /**
     * @ManyToOne(targetEntity="SitePage", inversedBy="blocks")
     * @JoinColumn(nullable=false)
     */
    protected $page;

    /**
     * @OneToMany(
     *     targetEntity="SiteBlockAttachment",
     *     mappedBy="block",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     * @OrderBy({"position" = "ASC"})
     */
    protected $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPage(SitePage $page)
    {
        $this->page = $page;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }
}
