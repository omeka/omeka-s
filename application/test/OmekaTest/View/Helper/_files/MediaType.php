<?php
namespace OmekaTest\View\Helper;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\View\Helper\MediaType\MediaTypeInterface;

class MediaType implements MediaTypeInterface
{
    public function form(MediaRepresentation $media = null, array $options = array())
    {
        return serialize($options);
    }

    public function render(MediaRepresentation $media, array $options = array())
    {
        return serialize($options);
    }
}
