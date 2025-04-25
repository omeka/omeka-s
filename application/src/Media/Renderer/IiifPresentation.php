<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $miradorConfig = [
            'window.sideBarOpen' => false,
            'selectedTheme' => 'light',
        ];
        if ($view->status()->isSiteRequest()) {
            // Respect site settings for the IIIF viewer.
            $miradorConfig['window.sideBarOpen'] = (bool) $view->siteSetting('iiif_viewer_sidebar', false);
            switch ($view->siteSetting('iiif_viewer_theme', 'light')) {
                case 'dark':
                    $miradorConfig['selectedTheme'] = 'dark';
                    break;
                case 'light':
                default:
                    $miradorConfig['selectedTheme'] = 'light';
            }
        }
        $query = [
            'url' => $media->source(),
            'mirador_config' => json_encode($miradorConfig),
        ];
        return $view->iiifViewer($query, $options);
    }
}
