<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\FileRenderer\Manager as FileRendererManager;
use Laminas\View\Renderer\PhpRenderer;

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

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        $mediaType = $media->mediaType();
        if ($this->fileRendererManager->has($mediaType)) {
            $renderer = $this->fileRendererManager->get($mediaType);
        } else {
            $extension = $media->extension();
            if ($this->fileRendererManager->has($extension)) {
                $renderer = $this->fileRendererManager->get($extension);
            } else {
                $renderer = $media->hasThumbnails()
                    ? $this->fileRendererManager->get('thumbnail')
                    : $this->fileRendererManager->get('fallback');
            }
        }
        return $renderer->render($view, $media, $options);
    }
}
