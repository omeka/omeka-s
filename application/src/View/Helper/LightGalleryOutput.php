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
        $view->headScript()->appendFile($view->assetUrl('js/lg-itemfiles-config.js', 'Omeka', true));
        $view->headLink()->prependStylesheet($view->assetUrl('vendor/lightgallery/css/lightgallery-bundle.min.css', 'Omeka'));
        $escape = $view->plugin('escapeHtml');

        $html = '<div id="itemfiles" class="media-list">';
        $mediaCaption = $view->themeSetting('media_caption');

        foreach ($files as $file) {
            $attribs = [];
            $media = $file['media'];
            if (!empty($file['forceThumbnail'])) {
                $source = $media->thumbnailUrl('large');
                $downloadUrl = $media->originalUrl() ?: $source;
            } else {
                $source = $downloadUrl = $media->originalUrl() ?: $media->source();
            }

            $mediaType = $media->mediaType();
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
                $attribs['data-video'] = $videoSrcJson;
            } elseif ($mediaType == 'application/pdf') {
                $attribs['data-iframe'] = 'true';
                $attribs['data-iframe-title'] = $media->altText();
                $attribs['data-src'] = $source;
            } else {
                $attribs['data-src'] = $source;
            }

            switch ($mediaCaption) {
                case 'title':
                    $attribs['data-sub-html'] = $media->displayTitle();
                    break;
                case 'description':
                    $attribs['data-sub-html'] = $media->displayDescription();
                    break;
                case 'none':
                default:
                    // no action
            }

            $attribs['data-thumb'] = $media->thumbnailDisplayUrl('medium');
            $attribs['data-download-url'] = $downloadUrl;
            $attribs['class'] = 'media resource';
            $attribs['title'] = $media->altText();

            $html .= '<div';
            foreach ($attribs as $attribName => $attribValue) {
                $html .= ' ' . $attribName . '="' . $escape($attribValue) . '"';
            }
            $html .= '></div>';
        }
        $html .= '</div>';

        return $html;
    }
}
