<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Renderer\PhpRenderer;

class Fallback implements RendererInterface
{
    use ServiceLocatorAwareTrait;

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        return '';
    }
}
