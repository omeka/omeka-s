<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;

interface FulltextSearchableInterface
{
    /**
     * Get the the text of the passed media.
     *
     * @param Media $media
     * @return string
     */
    public function getFulltextText(MediaRepresentation $media);
}
