<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class FallbackRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        return $view->hyperlink($media->filename(), $media->originalUrl());
    }
}
