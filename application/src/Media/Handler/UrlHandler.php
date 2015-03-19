<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractFileHandler;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Http\Exception\ExceptionInterface as HttpExceptionInterface;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class UrlHandler extends AbstractFileHandler
{
    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {}

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (!isset($data['ingest_uri'])) {
            $errorStore->addError('error', 'No URL ingest data specified');
            return;
        }

        $uri = new HttpUri($data['ingest_uri']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('ingest_uri', 'Invalid ingest URI specified');
            return;
        }

        $file = $this->getServiceLocator()->get('Omeka\StorableFile');
        $this->downloadFile($uri, $file->getTempPath());
        $originalName = $uri->getPath();
        $hasThumbnails = $file->storeThumbnails();
        $file->storeOriginal($originalName);

        $media->setFilename($file->getStorageName());
        $media->setMediaType($file->getMediaType());
        $media->setHasThumbnails($hasThumbnails);
        $media->setHasOriginal(true);

        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}
}
