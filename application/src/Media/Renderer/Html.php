<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class Html implements RendererInterface, FulltextSearchableInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        $data = $media->mediaData();
        return $data['html'];
    }

    public function getFulltextText(MediaRepresentation $media)
    {
        $data = $media->mediaData();
        return strip_tags($data['html']);
    }
}
