<?php
/**
 * Specific media-types that are not included in default Apache media types.
 *
 * This list is appended to the Apache list to build the Omeka list.
 */

return [
    'audio/mpeg' => [
        'mp3',
        'mpga',
        'mp2',
        'mp2a',
        'm2a',
        'm3a',
    ],
    'image/jp2' => [
        'jp2',
        'j2k',
    ],
];
