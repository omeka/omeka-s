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

class IiifManifest implements IngesterInterface
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
        return 'IIIF manifest'; // @translate
    }

    public function getRenderer()
    {
        return 'iiif_manifest';
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new Url('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'IIIF manifest URL', // @translate
            'info' => 'Enter the URL to the IIIF manifest.', // @translate
        ]);
        $urlInput->setAttributes([
            'required' => true,
        ]);
        return $view->formRow($urlInput);
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
    }
}
