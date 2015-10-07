<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Dom\Query;
use Zend\Form\Element\Text;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class OEmbed extends AbstractIngester
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('oEmbed');
    }

    public function getRenderer()
    {
        return 'oembed';
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No OEmbed URL specified');
            return;
        }

        $config = $this->getServiceLocator()->get('Config');
        $whitelist = $config['oembed']['whitelist'];
        
        $whitelisted = false;
        foreach ($whitelist as $regex) {
            if (preg_match($regex, $data['o:source']) === 1) {
                $whitelisted = true;
                break;
            }
        }

        if (!$whitelisted) {
            $errorStore->addError('o:source', 'Invalid OEmbed URL');
            return;
        }

        $source = $data['o:source'];

        $response = $this->makeRequest($source, 'OEmbed URL', $errorStore);
        if (!$response) {
            return;
        }

        $document = $response->getBody();
        $dom = new Query($document);
        $oEmbedLinks = $dom->queryXpath('//link[@rel="alternate" or @rel="alternative"][@type="application/json+oembed"]');
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
            $hasThumbnails = $fileManager->storeThumbnails($file);

            if ($hasThumbnails) {
                $media->setFilename($file->getStorageName());
                $media->setHasThumbnails(true);
            }
        }

        $media->setData($mediaData);
        $media->setSource($source);
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new Text('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => $view->translate('oEmbed URL'),
            'info' => $view->translate('URL for the media to embed.'),
        ]);
        $urlInput->setAttributes([
            'id' => 'media-oembed-source-__index__',
            'required' => true
        ]);
        return $view->formField($urlInput);
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
