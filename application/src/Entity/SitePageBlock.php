<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(
 *             name="page_position",
 *             columns={"page_id", "position"}
 *         )
 *     }
 * )
 */
class SitePageBlock extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(length=80)
     */
    protected $layout;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $data;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected $layoutData;

    /**
     * @ORM\Column(type="integer")
     */
    protected $position;

    /**
     * @ORM\ManyToOne(targetEntity="SitePage", inversedBy="blocks")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $page;

    /**
     * @ORM\OneToMany(
     *     targetEntity="SiteBlockAttachment",
     *     mappedBy="block",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     * @ORM\OrderBy({"position" = "ASC"})
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

    public function setLayoutData($layoutData)
    {
        $this->layoutData = $layoutData;
    }

    public function getLayoutData()
    {
        return $this->layoutData;
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
