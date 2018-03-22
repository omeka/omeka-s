<?php 
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Url as UrlElement;
use Zend\Http\Client as HttpClient;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class IIIF implements IngesterInterface
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var Downloader
     */
    protected $downloader;

    public function __construct(HttpClient $httpClient, Downloader $downloader)
    {
        $this->httpClient = $httpClient;
        $this->downloader = $downloader;
    }

    public function getLabel()
    {
        return 'IIIF image'; // @translate
    }

    public function getRenderer()
    {
        return 'iiif';
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No IIIF image URL specified');
            return;
        }
        $source = $data['o:source'];
        //Make a request and handle any errors that might occur.
        $uri = new HttpUri($source);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', "Invalid URL specified");
            return false;
        }
        $client = $this->httpClient;
        $client->reset();
        $client->setUri($uri);
        $response = $client->send();
        if (!$response->isOk()) {
            $errorStore->addError('o:source', sprintf(
                "Error reading %s: %s (%s)",
                $this->getLabel(),
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return false;
        }
        $IIIFData = json_decode($response->getBody(), true);
        if (!$IIIFData) {
            $errorStore->addError('o:source', 'Error decoding IIIF JSON');
            return;
        }
        //Check if valid IIIF data
        if ($this->validate($IIIFData)) {
            $media->setData($IIIFData);
        } else {
            $errorStore->addError('o:source', 'URL does not link to IIIF JSON');
            return;
        }

        // Check API version and generate a thumbnail
        if (isset($IIIFData['@context']) && $IIIFData['@context'] == 'http://iiif.io/api/image/2/context.json') {
            //Version 2.0
            $URLString = '/full/full/0/default.jpg';
        } else {
            // Earlier versions
            $URLString = '/full/full/0/native.jpg';
        }
        if (isset($IIIFData['@id'])) {
            $tempFile = $this->downloader->download($IIIFData['@id'] . $URLString);
            if ($tempFile) {
                if ($tempFile->storeThumbnails()) {
                    $media->setStorageId($tempFile->getStorageId());
                    $media->setHasThumbnails(true);
                }
                $tempFile->delete();
            }
        }
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'IIIF image URL', // @translate
            'info' => 'URL for the image to embed.', // @translate
        ]);
        $urlInput->setAttributes([
            'required' => true,
        ]);
        return $view->formRow($urlInput);
    }

    //This check comes from Open Seadragon's own validation check
    public function validate($IIIFData)
    {
        if (isset($IIIFData['protocol']) && $IIIFData['protocol'] == 'http://iiif.io/api/image') {
            // Version 2.0
            return true;
        } elseif (isset($IIIFData['@context']) && (
            // Version 1.1
            $IIIFData['@context'] == "http://library.stanford.edu/iiif/image-api/1.1/context.json" ||
            $IIIFData['@context'] == "http://iiif.io/api/image/1/context.json")) {
            // N.B. the iiif.io context is wrong, but where the representation lives so likely to be used
            return true;
        } elseif (isset($IIIFData['profile']) &&
            // Version 1.0
            $IIIFData['profile'][0]("http://library.stanford.edu/iiif/image-api/compliance.html") === 0) {
            return true;
        } elseif (isset($IIIFData['identifier']) && $IIIFData['width'] && $IIIFData['height']) {
            return true;
        } elseif (isset($IIIFData['documentElement']) &&
            "info" == $IIIFData['documentElement']['tagName'] &&
            "http://library.stanford.edu/iiif/image-api/ns/" ==
                $IIIFData['documentElement']['namespaceURI']) {
            return true;
        }
    }
}
