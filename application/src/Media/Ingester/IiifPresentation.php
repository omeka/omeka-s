<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Element\Url;
use Laminas\Http\Client as HttpClient;
use Laminas\Uri\Http as HttpUri;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements IngesterInterface
{
    protected $httpClient;

    protected $downloader;

    public function __construct(HttpClient $httpClient, Downloader $downloader)
    {
        $this->httpClient = $httpClient;
        $this->downloader = $downloader;
    }

    public function getLabel()
    {
        return 'IIIF presentation'; // @translate
    }

    public function getRenderer()
    {
        return 'iiif_presentation';
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new Url('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'IIIF presentation URL', // @translate
            'info' => 'Enter the URL to a IIIF collection or manifest.', // @translate
        ]);
        $urlInput->setAttributes([
            'required' => true,
        ]);
        return $view->formRow($urlInput);
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        // Validate the IIIF presentation URL.
        if (!(isset($data['o:source']) && '' !== trim($data['o:source']))) {
            $errorStore->addError('o:source', 'No IIIF presentation URL specified');
            return;
        }
        $uri = new HttpUri($data['o:source']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', 'Invalid IIIF presentation URL specified');
            return;
        }

        // Fetch the IIIF presenation.
        try {
            $response = $this->httpClient->setUri($uri)->send();
        } catch (\Exception $e) {
            $errorStore->addError('o:source', sprintf(
                'Error connecting to IIIF presentation: %s',
                $e->getMessage()
            ));
            return;
        }
        if (!$response->isOk()) {
            $errorStore->addError('o:source', sprintf(
                'Error reading IIIF presentation: %s (%s)',
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return;
        }
        $iiif = json_decode($response->getBody(), true);
        if (!$iiif) {
            $errorStore->addError('o:source', 'Error decoding IIIF presentation JSON');
            return;
        }

        // Validate the IIIF presentation and set it as media data.
        if (!$this->isValid($iiif)) {
            $errorStore->addError('o:source', 'Invalid IIIF presentation JSON');
            return;
        }
        $media->setData($iiif);

        // Generate a media thumbnail.
    }

    public function isValid(array $iiif)
    {
        if (!(isset($iiif['@context']) && isset($iiif['@type']))) {
            return false;
        }
        switch ($iiif['@context']) {
            // IIIF Presentation API 1.0
            case 'http://www.shared-canvas.org/ns/context.json':
            case 'http://iiif.io/api/presentation/1/context.json':
            case 'https://iiif.io/api/presentation/1/context.json':
                if (!in_array($iiif['@type'], ['sc:Manifest'])) {
                    return false;
                }
                break;
            // IIIF Presentation API 2.0
            case 'http://iiif.io/api/presentation/2/context.json':
            case 'https://iiif.io/api/presentation/2/context.json':
                if (!in_array($iiif['@type'], ['sc:Manifest', 'sc:Collection'])) {
                    return false;
                }
                break;
            // IIIF Presentation API 3.0
            case 'http://iiif.io/api/presentation/3/context.json':
            case 'https://iiif.io/api/presentation/3/context.json':
                if (!in_array($iiif['@type'], ['Manifest', 'Collection'])) {
                    return false;
                }
                break;
            default:
                return false;
        }
        return true;
    }
}
