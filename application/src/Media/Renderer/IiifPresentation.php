<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $query = [
            'url' => $media->source(),
            'mirador_config' => json_encode([
                'window.sideBarOpen' => false,
            ]),
        ];
        return $view->iiifViewer($query, $options);
    }
}
