<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

/**
 * Stored file media renderer.
 */
class File implements RendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        $filename = $media->filename();
        $url = $view->basePath('files/' . $filename);
        return $view->hyperlink($filename, $url);
    }
}
