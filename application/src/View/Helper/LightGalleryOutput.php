<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class LightGalleryOutput extends AbstractHelper
{
    public function __invoke($files = null)
    {
        if (!isset($files)) {
            return;
        }
        $view = $this->getView();
        $view->headScript()->prependFile($view->assetUrl('vendor/lightgallery/lightgallery.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/thumbnail/lg-thumbnail.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/zoom/lg-zoom.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/video/lg-video.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/rotate/lg-rotate.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/hash/lg-hash.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/lg-itemfiles-config.js', 'Omeka'));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lightgallery-bundle.min.css', 'Omeka'));
        $escape = $view->plugin('escapeHtml');

        $html = '<div id="itemfiles" class="media-list">';
        $mediaCaption = $view->themeSetting('media_caption');

        foreach ($files as $file) {
            $media = $file['media'];
            $source = ($media->originalUrl()) ? $media->originalUrl() : $media->source();
            $mediaCaptionOptions = [
                'none' => '',
                'title' => 'data-sub-html="' . $media->displayTitle() . '"',
                'description' => 'data-sub-html="' . $media->displayDescription() . '"',
            ];
            $mediaCaptionAttribute = ($mediaCaption) ? $mediaCaptionOptions[$mediaCaption] : '';
            $mediaType = $media->mediatype();
            if (null !== $mediaType && strpos($mediaType, 'video') !== false) {
                $videoSrcObject = [
                    'source' => [
                        [
                            'src' => $source,
                            'type' => $mediaType,
                        ],
                    ],
                    'attributes' => [
                        'preload' => false,
                        'playsinline' => true,
                        'controls' => true,
                    ],
                ];
                if (isset($file['tracks'])) {
                    foreach ($file['tracks'] as $key => $track) {
                        $label = $track->displayTitle();
                        $srclang = (string) $track->value('dcterms:language', ['default' => '']);
                        $type = (string) $track->value('dcterms:type', ['default' => 'captions']);
                        $videoSrcObject['tracks'][$key]['src'] = $track->originalUrl();
                        $videoSrcObject['tracks'][$key]['label'] = $label;
                        $videoSrcObject['tracks'][$key]['srclang'] = $srclang;
                        $videoSrcObject['tracks'][$key]['kind'] = $type;
                    }
                }
                $videoSrcJson = json_encode($videoSrcObject);
                $html .= '<div data-video="' . $escape($videoSrcJson) . '" ' . $mediaCaptionAttribute . 'data-thumb="' . $escape($media->thumbnailUrl('medium')) . '" data-download-url="' . $source . '" class="media resource">';
            } elseif ($mediaType == 'application/pdf') {
                $html .= '<div data-iframe="' . $escape($source) . '" ' . $mediaCaptionAttribute . 'data-src="' . $source . '" data-thumb="' . $escape($media->thumbnailUrl('medium')) . '" data-download-url="' . $source . '" class="media resource">';
            } else {
                $html .= '<div data-src="' . $source . '" ' . $mediaCaptionAttribute . 'data-thumb="' . $escape($media->thumbnailUrl('medium')) . '" data-download-url="' . $source . '" class="media resource">';
            }
            $html .= $media->render();
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
