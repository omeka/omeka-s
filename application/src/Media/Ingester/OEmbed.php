<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Oembed as StdlibOembed;
use Laminas\Form;
use Laminas\View\Renderer\PhpRenderer;

class OEmbed implements MutableIngesterInterface
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
        $form = new Form\Form;
        $form->add([
            'type' => Form\Element\Url::class,
            'name' => 'o:media[__index__][o:source]',
            'options' => [
                'label' => 'oEmbed URL', // @translate
                'info' => 'URL for the media to embed.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        return $view->formCollection($form, false);
    }

    public function update(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if ($data['refresh_oembed']) {
            $oembed = $this->oembed->getOembed($media->getSource(), $errorStore, 'o:source');
            if (!$oembed) {
                return;
            }
            $media->setData($oembed);
        }
    }

    public function updateForm(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $form = new Form\Form;
        $form->add([
            'type' => Form\Element\Text::class,
            'name' => 'oembed_url',
            'options' => [
                'label' => 'oEmbed URL',
            ],
            'attributes' => [
                'value' => $media->source(),
                'disabled' => true,
            ],
        ]);
        $form->add([
            'type' => Form\Element\Textarea::class,
            'name' => 'oembed_oembed',
            'options' => [
                'label' => 'oEmbed',
            ],
            'attributes' => [
                'value' => json_encode($media->mediaData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'rows' => 8,
                'disabled' => true,
            ],
        ]);
        $form->add([
            'type' => Form\Element\Checkbox::class,
            'name' => 'refresh_oembed',
            'options' => [
                'label' => 'Refresh oEmbed',
            ],
        ]);
        return $view->formCollection($form, false);
    }
}
