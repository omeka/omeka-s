<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Renderer\PhpRenderer;

class ImageRenderer implements RendererInterface
{
    use ServiceLocatorAwareTrait;

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ){
        return sprintf('<img src="%s">', $view->escapeHtml($media->originalUrl()));
    }
}
