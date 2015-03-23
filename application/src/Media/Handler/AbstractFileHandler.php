<?php
namespace Omeka\Media\Handler;

use finfo;
use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Media\Handler\AbstractHandler;
use Zend\Math\Rand;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\View\Renderer\PhpRenderer;

abstract class AbstractFileHandler extends AbstractHandler
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
            $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
            $url = $fileStore->getUri('original/' . $media->filename());
            return $view->hyperlink($media->filename(), $url);
        }
    }
}
