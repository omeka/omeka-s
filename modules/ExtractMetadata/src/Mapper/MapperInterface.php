<?php
namespace ExtractMetadata\Mapper;

use Omeka\Entity;

/**
 * Interface for metadata mappers.
 */
interface MapperInterface
{
    /**
     * Get the label of this mapper.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Map extracted metadata.
     *
     * @param Entity\Media $mediaEntity
     * @param array $metadataEntities An array of metadata entities
     */
    public function map(Entity\Media $mediaEntity, array $metadataEntities);
}
