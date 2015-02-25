<?php
namespace OmekaTest\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Media\Renderer\RendererInterface;

class Renderer implements RendererInterface
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
