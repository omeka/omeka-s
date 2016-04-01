<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

class ImageRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        $thumbnailType = isset($options['thumbnailType']) ? $options['thumbnailType'] : 'large';
        return sprintf('<img src="%s">', $view->escapeHtml($media->thumbnailUrl($thumbnailType)));
    }
}
