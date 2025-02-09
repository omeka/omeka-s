<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class SortMedia extends AbstractHelper
{
    public function __invoke($files = null)
    {
        $sortedMedia = [];
        $whitelist = ['image/bmp', 'image/gif', 'image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'video/flv', 'video/x-flv', 'video/mp4', 'video/m4v',
                    'video/webm', 'video/wmv', 'video/quicktime', 'application/pdf', ];
        $html5videos = [];
        $mediaCount = 0;

        foreach ($files as $media) {
            $mediaType = $media->mediaType();
            $mediaRenderer = $media->renderer();
            if (in_array($mediaType, $whitelist) || (strpos($mediaRenderer, 'youtube') !== false)) {
                $sortedMedia['lightMedia'][$mediaCount]['media'] = $media;
                if (null !== $mediaType && strpos($mediaType, 'video') !== false) {
                    $html5videos[$mediaCount] = pathinfo($media->source(), PATHINFO_FILENAME);
                    $sortedMedia['lightMedia'][$mediaCount]['tracks'] = [];
                }
                $mediaCount++;
            } else {
                $sortedMedia['otherMedia'][] = $media;
            }
        }
        if ((count($html5videos) > 0) && isset($sortedMedia['otherMedia'])) {
            foreach ($html5videos as $fileId => $filename) {
                foreach ($sortedMedia['otherMedia'] as $key => $otherMedia) {
                    if ($otherMedia->source() == "$filename.vtt") {
                        $sortedMedia['lightMedia'][$fileId]['tracks'][] = $otherMedia;
                        unset($sortedMedia['otherMedia'][$key]);
                    }
                }
            }
        }

        return $sortedMedia;
    }
}
