<?php
namespace Mapping\Entity;

use LongitudeOne\Spatial\PHP\Types\Geography\GeographyInterface;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;
use Omeka\Entity\Media;

/**
 * @Entity
 */
class MappingFeature extends AbstractEntity
{
    /**
     * @Id
     * @Column(
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Item",
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $item;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Media",
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $media;

    /**
     * @Column(
     *     nullable=true
     * )
     */
    protected $label;

    /**
     * @Column(
     *     type="geography"
     * )
     */
    protected $geography;

    public function getId()
    {
        return $this->id;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setMedia(?Media $media)
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setLabel(?string $label)
    {
        $this->label = is_string($label) && '' === trim($label) ? null : $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get geography.
     *
     * @return GeographyInterface
     */
    public function getGeography()
    {
        return $this->geography;
    }

    /**
     * Set geography.
     *
     * @param GeographyInterface $geography Geography to set
     */
    public function setGeography(GeographyInterface $geography)
    {
        $this->geography = $geography;
    }
}
