<?php
namespace OmekaTest\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Media\Renderer\RendererInterface;
use Zend\View\Renderer\PhpRenderer;

class Renderer implements RendererInterface
{
    public function form(PhpRenderer $view, array $options = array())
    {
        return serialize($options);
    }

    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        return serialize($options);
    }
}
