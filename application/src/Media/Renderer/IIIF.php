<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IIIF implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $view->headScript()->appendFile($view->assetUrl('vendor/openseadragon/openseadragon.min.js', 'Omeka'));

        $id = sprintf('iiif-%s', $media->id());
        $prefixUrl = $view->assetUrl('vendor/openseadragon/images/', 'Omeka', false, false);
        $tileSources = json_encode($media->mediaData());
        $height = '400px';
        if (isset($options['height'])) {
            $height = sprintf('%spx', (int) $options['height']);
        }

        $html = <<<'HTML'
        <div class="openseadragon" id="%1$s" data-prefix-url="%2$s" data-tile-sources="%3$s" style="height: %4$s;"></div>
        <script>
        {
            const openSeadragonDiv = document.getElementById('%1$s');
            const viewer = OpenSeadragon({
                id: '%1$s',
                prefixUrl: openSeadragonDiv.dataset.prefixUrl,
                tileSources: [JSON.parse(openSeadragonDiv.dataset.tileSources)]
            });
        }
        </script>
        HTML;
        return sprintf(
            $html,
            $id,
            $view->escapeHtml($prefixUrl),
            $view->escapeHtml($tileSources),
            $height
        );
    }
}
