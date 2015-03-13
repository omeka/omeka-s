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
            $url = $fileStore->getUri($media->filename());
            return $view->hyperlink($media->filename(), $url);
        }
    }

    /**
     * Get a random base name for the ingested file.
     *
     * @param string $extension The filename extension to append
     * @return string
     */
    public function getLocalBaseName($extension = null)
    {
        $baseName = bin2hex(Rand::getBytes(20));
        if ($extension) {
            $baseName .= '.' . $extension;
        }
        return $baseName;
    }

    /**
     * Detect and get an Internet media type.
     *
     * @uses finfo
     * @param string $filename The path to a file
     * @return string
     */
    public function getMediaType($filename)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($filename);
    }

    /**
     * Get a filename extension.
     *
     * Returns the original extension if the file already has one. Otherwise it
     * returns the first extension found from a map between Internet media types
     * and extensions.
     *
     * @param string $originalFile The original file name
     * @param string $mediaType The file's Internet media type
     * @return string
     */
    public function getExtension($originalFile, $mediaType)
    {
        $mediaTypeExtensionMap = $this->getServiceLocator()
            ->get('Omeka\MediaTypeExtensionMap');
        $extension = substr(strrchr($originalFile, '.'), 1);
        if (!$extension && isset($mediaTypeExtensionMap[$mediaType][0])) {
            $extension = $mediaTypeExtensionMap[$mediaType][0];
        }
        return $extension;
    }

    /**
     * Process and store a file from temporary storage
     *
     * @param Omeka\Model\Entity\Media $media
     * @param string $path
     * @param string $originalName
     */
    protected function processFile($media, $path, $originalName)
    {
        $mediaType = $this->getMediaType($path);
        $extension = $this->getExtension($originalName, $mediaType);
        $baseName = $this->getLocalBaseName($extension);

        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        $fileStore->put($path, $baseName);

        $media->setFilename($baseName);
        $media->setMediaType($mediaType);
    }
}
