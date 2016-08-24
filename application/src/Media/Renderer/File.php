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
        } catch (ServiceNotFoundException $e) {
            try {
                $renderer = $this->fileRendererManager->get($media->extension());
            } catch (ServiceNotFoundException $e) {
                if ($media->hasThumbnails()) {
                    $renderer = $this->fileRendererManager->get('thumbnail');
                } else {
                    $renderer = $this->fileRendererManager->get('fallback');
                }
            }
        }
        return $renderer->render($view, $media, $options);
    }
}
