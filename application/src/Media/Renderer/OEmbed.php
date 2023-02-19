<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Stdlib\Oembed as StdlibOembed;
use Laminas\View\Renderer\PhpRenderer;

class OEmbed implements RendererInterface
{
    protected $oembed;

    public function __construct(StdlibOembed $oembed)
    {
        $this->oembed = $oembed;
    }

    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $data = $media->mediaData();
        $markup = $this->oembed->renderOembed($view, $data);
        if ($markup) {
            return $markup;
        }
        $source = $media->source();
        $title = empty($data['title']) ? $source : $data['title'];
        return $view->hyperlink($title, $source);
    }
}
