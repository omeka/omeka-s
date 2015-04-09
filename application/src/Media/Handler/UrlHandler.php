<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractFileHandler;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\Http\Exception\ExceptionInterface as HttpExceptionInterface;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class UrlHandler extends AbstractFileHandler
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('URL');
    }

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {}

    /**
     * Ingest from a URL.
     *
     * Accepts the following non-prefixed keys:
     *
     * + ingest_url: (required) The URL to ingest. The idea is that some URLs
     *   contain sensitive data that should not be saved to the database, such
     *   as private keys. To preserve the URL, remove sensitive data from the
     *   URL and set it to o:source.
     * + store_original: (optional, default true) Whether to store an original
     *   file. This is helpful when you want the media to have thumbnails but do
     *   not need the original file.
     *
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (!isset($data['ingest_url'])) {
            $errorStore->addError('error', 'No ingest URL specified');
            return;
        }

        $uri = new HttpUri($data['ingest_url']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('ingest_url', 'Invalid ingest URL');
            return;
        }

        $file = $this->getServiceLocator()->get('Omeka\File');
        $file->setSourceName($uri->getPath());
        $this->downloadFile($uri, $file->getTempPath());

        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        $hasThumbnails = $fileManager->storeThumbnails($file);
        $media->setHasThumbnails($hasThumbnails);

        if (!isset($data['store_original']) || $data['store_original']) {
            $fileManager->storeOriginal($file);
            $media->setHasOriginal(true);
        }

        $media->setFilename($file->getStorageName());
        $media->setMediaType($file->getMediaType());

        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {
        $urlInput = new Text('o:media[__index__][ingest_url]');
        $urlInput->setOptions(array(
            'label' => $view->translate('URL'),
            'info' => $view->translate('A URL to the media.'),
        ));
        $urlInput->setAttributes(array(
            'id' => 'media-url-ingest-url-__index__',
            'required' => true
        ));
        return $view->formField($urlInput);
    }
}
