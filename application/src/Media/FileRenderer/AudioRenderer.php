<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

class AudioRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        return sprintf(
            '<audio src="%s" controls>%s</audio>',
            $view->escapeHtml($media->originalUrl()),
            $view->hyperlink($media->filename(), $media->originalUrl())
        );
    }
}
