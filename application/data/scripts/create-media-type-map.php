<?php
/**
 * Create a map between Internet media types and file extensions.
 *
 * Parses a existing map published by Apache that is used by many other
 * projects. We use this map when a file's media type is known but there is no
 * file extension.
 */
$url = 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
$mediaTypes = [];
foreach (explode(PHP_EOL, file_get_contents($url)) as $mt) {
    if ('#' == substr($mt, 0, 1) || !$mt) {
        continue;
    }
    preg_match_all('/[^\s]+/', $mt, $matches);
    $mediaTypes[array_shift($matches[0])] = $matches[0];
}

$specificMediaTypes = include dirname(__DIR__) . '/media-types/media-type-specific.php';
$mediaTypes = array_merge($mediaTypes, $specificMediaTypes);
ksort($mediaTypes);

$data = sprintf("<?php\nreturn %s;\n", var_export($mediaTypes, true));
file_put_contents(dirname(__DIR__) . '/media-types/media-type-map.php', $data);
