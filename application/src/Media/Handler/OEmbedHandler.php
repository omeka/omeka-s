<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\HandlerInterface;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Dom\Query;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class OEmbedHandler extends AbstractHandler
{
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No OEmbed URL specified');
        }
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $source = $data['o:source'];

        $response = $this->makeRequest($source, 'OEmbed URL', $errorStore);
        if (!$response) {
            return;
        }

        $document = $response->getBody();
        $dom = new Query($document);
        $oEmbedLinks = $dom->queryXpath('//link[@rel="alternate" and @type="application/json+oembed"]');
        if (!count($oEmbedLinks)) {
            $errorStore->addError('o:source', 'No OEmbed links were found at the given URI');
            return;
        }

        $oEmbedLink = $oEmbedLinks[0];
        $linkResponse = $this->makeRequest($oEmbedLink->getAttribute('href'),
            'OEmbed link URL', $errorStore);
        if (!$linkResponse) {
            return;
        }

        $mediaData = json_decode($linkResponse->getBody(), true);
        if (!$mediaData) {
            $errorStore->addError('o:source', 'Error decoding OEmbed JSON');
            return;
        }

        if (isset($mediaData['thumbnail_url'])) {

            $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
            $file = $this->getServiceLocator()->get('Omeka\File');

            $this->downloadFile($mediaData['thumbnail_url'], $file->getTempPath());
            //~ $hasThumbnails = $fileManager->storeThumbnails($file);

            //~ if ($hasThumbnails) {
                //~ $media->setFilename($file->getStorageName());
                //~ $media->setHasThumbnails(true);
            //~ }
        }

        $media->setData($mediaData);
        $media->setSource($source);
    }

    public function form(PhpRenderer $view, array $options = array())
    {}

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        $data = $media->mediaData();

        if ($data['type'] == 'photo') {
            $url = $data['url'];
            $width = $data['width'];
            $height = $data['height'];
            if (!empty($data['title'])) {
                $title = $data['title'];
            } else {
                $title = $url;
            }
            return sprintf(
                '<img src="%s" width="%s" height="%s" alt="%s">',
                $view->escapeHtmlAttr($url),
                $view->escapeHtmlAttr($width),
                $view->escapeHtmlAttr($height),
                $view->escapeHtmlAttr($title)
            );
        } else if (!empty($data['html'])) {
            return $data['html'];
        } else {
            $source = $media->source();
            if (!$empty($data['title'])) {
                $title = $data['title'];
            } else {
                $title = $source;
            }
            return $view->hyperlink($title, $source);
        }
    }

    /**
     * Make a request and handle any errors that might occur.
     *
     * @param string $url URL to request
     * @param string $type Type of URL (used to compose error messages)
     * @param ErrorStore $errorStore
     */
    protected function makeRequest($url, $type, ErrorStore $errorStore)
    {
        $uri = new HttpUri($url);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', "Invalid $type specified");
            return false;
        }

        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($uri);
        $response = $client->send();

        if (!$response->isOk()) {
            $errorStore->addError('o:source', sprintf(
                "Error reading %s: %s (%s)",
                $type,
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return false;
        }

        return $response;
    }
}
