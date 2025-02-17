<?php
namespace ExtractMetadata\Entity;

use DateTime;
use Omeka\Entity;

/**
 * @Entity
 *
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             name="media_extractor",
 *             columns={"media_id", "extractor"}
 *         )
 *     }
 * )
 */
class ExtractMetadata extends Entity\AbstractEntity
{
    /**
     * @Id
     * @Column(
     *     type="integer",
     *     options={"unsigned"=true}
     * )
     * @GeneratedValue(
     *     strategy="AUTO"
     * )
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Media"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $media;

    /**
     * @Column(
     *     type="datetime",
     *     nullable=false
     * )
     */
    protected $extracted;

    /**
     * @Column(
     *     type="string",
     *     nullable=false
     * )
     */
    protected $extractor;

    /**
     * @Column(
     *     type="json",
     *     nullable=false
     * )
     */
    protected $metadata = [];

    public function getId()
    {
        return $this->id;
    }

    public function setMedia(Entity\Media $media) : void
    {
        $this->media = $media;
    }

    public function getMedia() : Entity\Media
    {
        return $this->media;
    }

    public function setExtracted(DateTime $extracted) : void
    {
        $this->extracted = $extracted;
    }

    public function getExtracted() : DateTime
    {
        return $this->extracted;
    }

    public function setExtractor(string $extractor) : void
    {
        $this->extractor = $extractor;
    }

    public function getExtractor() : string
    {
        return $this->extractor;
    }

    public function setMetadata(array $metadata) : void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata() : array
    {
        return $this->metadata;
    }
}
