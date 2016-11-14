<?php 
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Manager as FileManager;
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
     * @var FileManager
     */
    protected $fileManager;

    public function __construct(HttpClient $httpClient, FileManager $fileManager)
    {
        $this->httpClient = $httpClient;
        $this->fileManager = $fileManager;
    }

    public function getLabel()
    {
        return 'IIIF Image'; // @translate
    }

    public function getRenderer()
    {
        return 'iiif';
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No IIIF Image URL specified');
            return;
        }
        $source = $data['o:source'];
        //Make a request and handle any errors that might occur.
        $uri = new HttpUri($source);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', "Invalid url specified");
            return false;
        }
        $client = $this->httpClient;
        $client->reset();
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
        $IIIFData = json_decode($response->getBody(), true);
        if(!$IIIFData) {
            $errorStore->addError('o:source', 'Error decoding IIIF JSON');
            return;
        }
        //Check if valid IIIF data
        if($this->validate($IIIFData)) {
            $media->setData($IIIFData);
        // Not IIIF
        } else {
            $errorStore->addError('o:source', 'URL does not link to IIIF JSON');
            return;
        }

        //Check API version and generate a thumbnail
        //Version 2.0
        if (isset($IIIFData['@context']) && $IIIFData['@context'] == 'http://iiif.io/api/image/2/context.json') {
            $URLString = '/full/full/0/default.jpg';
        // Earlier versions
        } else  {
            $URLString = '/full/full/0/native.jpg';
        }
        if (isset($IIIFData['@id'])) {
            $fileManager = $this->fileManager;
            $file = $fileManager->getTempFile();
            if ($fileManager->downloadFile($IIIFData['@id'] . $URLString, $file->getTempPath())) {
                if ($fileManager->storeThumbnails($file)) {
                    $media->setStorageId($file->getStorageId());
                    $media->setHasThumbnails(true);
                }
            }
            $file->delete();
        }
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'IIIF Image URL', // @translate
            'info' => 'URL for the image to embed.', // @translate
        ]);
        return $view->formRow($urlInput);
    }

    //This check comes from Open Seadragon's own validation check
    public function validate($IIIFData) {
        // Version 2.0
        if (isset($IIIFData['protocol']) && $IIIFData['protocol'] == 'http://iiif.io/api/image') {
                return true;
        // Version 1.1
        } else if (isset($IIIFData['@context']) && (
            $IIIFData['@context'] == "http://library.stanford.edu/iiif/image-api/1.1/context.json" ||
            $IIIFData['@context'] == "http://iiif.io/api/image/1/context.json")) {
            // N.B. the iiif.io context is wrong, but where the representation lives so likely to be used
                return true;
        // Version 1.0
        } else if (isset($IIIFData['profile']) &&
            $IIIFData['profile'][0]("http://library.stanford.edu/iiif/image-api/compliance.html") === 0) {
                return true;
        } else if (isset($IIIFData['identifier']) && $IIIFData['width'] && $IIIFData['height']) {
                return true;
        } else if (isset($IIIFData['documentElement']) &&
            "info" == $IIIFData['documentElement']['tagName'] &&
            "http://library.stanford.edu/iiif/image-api/ns/" ==
                $IIIFData['documentElement']['namespaceURI']) {
                return true;
        }
    }
}
