<?php 
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Url as UrlElement;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class IIIF extends AbstractIngester
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('IIIF Image');
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
            $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
            $file = $this->getServiceLocator()->get('Omeka\File');
            $this->downloadFile($IIIFData['@id'] . $URLString, $file->getTempPath());
            $hasThumbnails = $fileManager->storeThumbnails($file);
            if ($hasThumbnails) {
                $media->setFilename($file->getStorageName());
                $media->setHasThumbnails(true);
            }
        }
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => $view->translate('IIIF Image URL'),
            'info' => $view->translate('URL for the image to embed.'),
        ]);
        return $view->formField($urlInput);
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
