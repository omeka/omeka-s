<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Oembed as StdlibOembed;
use Laminas\Dom\Query;
use Laminas\Form\Element\Url as UrlElement;
use Laminas\Http\Client as HttpClient;
use Laminas\Uri\Http as HttpUri;
use Laminas\View\Renderer\PhpRenderer;

class OEmbed implements IngesterInterface
{
    protected $oembed;

    /**
     * @var Downloader
     */
    protected $downloader;

    public function __construct(StdlibOembed $oembed, Downloader $downloader)
    {
        $this->oembed = $oembed;
        $this->downloader = $downloader;
    }

    public function getLabel()
    {
        return 'oEmbed'; // @translate
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
        $oembed = $this->oembed->getOembed($data['o:source'], $errorStore, 'o:source');
        if (!$oembed) {
            return;
        }
        if (isset($oembed['thumbnail_url'])) {
            $tempFile = $this->downloader->download($oembed['thumbnail_url']);
            if ($tempFile) {
                $tempFile->mediaIngestFile($media, $request, $errorStore, false);
            }
        }
        $media->setData($oembed);
        $media->setSource($data['o:source']);
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'oEmbed URL', // @translate
            'info' => 'URL for the media to embed.', // @translate
        ]);
        $urlInput->setAttributes([
            'id' => 'media-oembed-source-__index__',
            'required' => true,
        ]);
        return $view->formRow($urlInput);
    }
}
