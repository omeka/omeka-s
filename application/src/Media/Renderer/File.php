<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\View\Renderer\PhpRenderer;

class File extends AbstractRenderer
{
    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        try {
            $renderer = $this->getServiceLocator()
                ->get('Omeka\FileRendererManager')
                ->get($media->mediaType());
            return $renderer->render($view, $media, $options);
        } catch (ServiceNotFoundException $e) {
            return $view->hyperlink($media->filename(), $media->originalUrl());
        }
    }
}
