<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\FileRenderer\Manager as FileRendererManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\View\Renderer\PhpRenderer;

class File implements RendererInterface
{
    /**
     * @var FileRendererManager
     */
    protected $fileRendererManager;

    /**
     * @param FileRendererManager $fileRendererManager
     */
    public function __construct(FileRendererManager $fileRendererManager)
    {
        $this->fileRendererManager = $fileRendererManager;
    }

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        try {
            $renderer = $this->fileRendererManager->get($media->mediaType());
            return $renderer->render($view, $media, $options);
        } catch (ServiceNotFoundException $e) {
            return $view->hyperlink($media->filename(), $media->originalUrl());
        }
    }
}
