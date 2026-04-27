<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $miradorConfig = [
            'window.sideBarOpen' => (bool) $view->fallbackSetting('iiif_viewer_sidebar', ['site'], false),
        ];
        switch ($view->fallbackSetting('iiif_viewer_theme', ['site'], 'light')) {
            case 'dark':
                $miradorConfig['selectedTheme'] = 'dark';
                break;
            case 'light':
            default:
                $miradorConfig['selectedTheme'] = 'light';
        }
        $query = [
            'url' => $media->source(),
            'mirador_config' => json_encode($miradorConfig),
        ];
        return $view->iiifViewer($query, $options);
    }
}
