<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class LightGalleryOutput extends AbstractHelper
{
    public function __invoke($files = null)
    {
        $view = $this->getView();
        $view->headScript()->prependFile($view->assetUrl('vendor/lightgallery/lightgallery.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/thumbnail/lg-thumbnail.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/zoom/lg-zoom.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/video/lg-video.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/rotate/lg-rotate.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('vendor/lightgallery/plugins/hash/lg-hash.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/lg-itemfiles-config.js', 'Omeka'));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lg-thumbnail.css', 'Omeka'));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lg-zoom.css', 'Omeka'));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lg-video.css', 'Omeka'));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lg-rotate.css', 'Omeka'));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lightgallery.css', 'Omeka'));
        $escape = $view->plugin('escapeHtml');

        $html = '<ul id="itemfiles" class="media-list">';
        $mediaCaption = $view->themeSetting('media_caption');

        foreach ($files as $file) {
            $media = $file['media'];
            $source = ($media->originalUrl()) ? $media->originalUrl() : $media->source();
            $mediaCaptionOptions = [
                'none' => '',
                'title' => 'data-sub-html="' . $media->displayTitle() . '"',
                'description' => 'data-sub-html="'. $media->displayDescription() . '"'
            ];
            $mediaCaptionAttribute = ($mediaCaption) ? $mediaCaptionOptions[$mediaCaption] : '';
            $mediaType = $media->mediatype();
            if (strpos($mediaType, 'video') !== false) {
                $videoSrcObject = [
                    'source' => [
                        [
                            'src' => $source,
                            'type' => $mediaType,
                        ]
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
                        $srclang = ($track->value('Dublin Core, Language')) ? $track->value('dcterms:language') : '';
                        $type = ($track->value('Dublin Core, Type')) ? $track->value('dcterms:type') : 'captions';
                        $videoSrcObject['tracks'][$key]['src'] = $track->originalUrl();
                        $videoSrcObject['tracks'][$key]['label'] = $label;
                        $videoSrcObject['tracks'][$key]['srclang'] = $srclang;
                        $videoSrcObject['tracks'][$key]['kind'] = $type;
                    }
                }
                $videoSrcJson = json_encode($videoSrcObject);
                $html .=  '<li data-video="' . $escape($videoSrcJson) . '" ' . $mediaCaptionAttribute . 'data-thumb="' . $escape($media->thumbnailUrl('medium')) . '" data-download-url="' . $source . '" class="media resource">';
            } else if ($mediaType == 'application/pdf') {
                $html .=  '<li data-iframe="' . $escape($source) . '" '. $mediaCaptionAttribute . 'data-src="' . $source . '" data-thumb="' . $escape($media->thumbnailUrl('medium')) . '" data-download-url="' . $source . '" class="media resource">';
            } else {
                $html .=  '<li data-src="' . $source . '" ' . $mediaCaptionAttribute . 'data-thumb="' . $escape($media->thumbnailUrl('medium')) . '" data-download-url="' . $source . '" class="media resource">';
            }
            $html .= $media->render();
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}
