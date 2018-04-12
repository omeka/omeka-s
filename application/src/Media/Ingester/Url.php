<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\File\Validator;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Url as UrlElement;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class Url implements IngesterInterface
{
    /**
     * @var Downloader
     */
    protected $downloader;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(Downloader $downloader, Validator $validator)
    {
        $this->downloader = $downloader;
        $this->validator = $validator;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'URL'; // @translate
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer()
    {
        return 'file';
    }

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

        $tempFile = $this->downloader->download($uri, $errorStore);
        if (!$tempFile) {
            return;
        }
        $tempFile->setSourceName($uri->getPath());
        if (!$this->validator->validate($tempFile, $errorStore)) {
            return;
        }
        $media->setStorageId($tempFile->getStorageId());
        $media->setExtension($tempFile->getExtension());
        $media->setMediaType($tempFile->getMediaType());
        $media->setSha256($tempFile->getSha256());
        $media->setSize($tempFile->getSize());
        $hasThumbnails = $tempFile->storeThumbnails();
        $media->setHasThumbnails($hasThumbnails);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
        if (!isset($data['store_original']) || $data['store_original']) {
            $tempFile->storeOriginal();
            $media->setHasOriginal(true);
        }
        $tempFile->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][ingest_url]');
        $urlInput->setOptions([
            'label' => 'URL', // @translate
            'info' => 'A URL to the media.', // @translate
        ]);
        $urlInput->setAttributes([
            'id' => 'media-url-ingest-url-__index__',
            'required' => true,
        ]);
        return $view->formRow($urlInput);
    }
}
