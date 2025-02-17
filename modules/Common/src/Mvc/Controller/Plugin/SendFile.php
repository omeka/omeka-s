<?php declare(strict_types=1);

namespace Common\Mvc\Controller\Plugin;

use finfo;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Api\Representation\AssetRepresentation;
use Omeka\Api\Representation\MediaRepresentation;
use Omeka\File\TempFile as OmekaFile;

class SendFile extends AbstractPlugin
{
    /**
     * Send a file as stream directly to the user. Content ranges are managed.
     *
     * This method is used when the file should be checked first for rights or
     * statistics or when the direct url to the file should be hidden.
     *
     * If there is a link, it is recommended to set attribute "download" to link
     * tag "<a>" when the file is for download and not for display in the page.
     *
     * @param string $filepath
     * @param array $params Options for the http headers:
     * - content_type (string): the content type to set in the headers, that is
     *   the media type of the file, or automatically defined from the file.
     * - filename (string): filename for download; default based on filepath.
     * - disposition_mode (string): "inline" (default) or "attachment".
     * - cache (boolean|int): set or disable cache; default 30 days  as seconds.
     * - resource (MediaRepresentation|AssetRepresentation): allow to get the
     *   media type automatically.
     * - storage_type (string): allow to get the media type automatically when
     *   the derivative of a resource is required.
     * - skip_empty_file (bool): do not return an empty file (false by default).
     * - bypass_range (bool): do not accept range (false by default, so accept
     *   ranges by default).
     * @return \Laminas\Http\Response|null Return null when there is no file or
     *   the file is empty and option "skip_empty_file" is set.
     */
    public function __invoke(string $filepath, array $params): ?HttpResponse
    {
        // A security. Don't check the realpath to avoid issue on some configs.
        if (strpos($filepath, '../') !== false
            || !file_exists($filepath)
            || !is_readable($filepath)
            || is_dir($filepath)
        ) {
            return null;
        }

        $filesize = (int) filesize($filepath);
        if (!$filesize && !empty($params['skip_empty_file'])) {
            return null;
        }

        $contentType = $params['content_type'] ?? $params['media_type'] ?? null;
        if (!$contentType && !empty($params['resource'])) {
            if ($params['resource'] instanceof MediaRepresentation) {
                $media = $params['resource'];
                $storageType = $params['storage_type'] ?? null;
                $contentType = $storageType === 'original' ? $media->mediaType() : 'image/jpeg';
            } elseif ($params['resource'] instanceof AssetRepresentation) {
                $asset = $params['resource'];
                $contentType = $asset->mediaType();
            }
        }
        if (!$contentType) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mediaType = $finfo->file($filepath);
            $contentType = $mediaType
                ? OmekaFile::MEDIA_TYPE_ALIASES[$mediaType] ?? $mediaType
                : 'application/octet-stream';
        }

        $filename = isset($params['filename']) && strlen(trim($params['filename']))
            ? trim($params['filename'])
            : basename($filepath);

        $dispositionMode = ($params['disposition_mode'] ?? null) === 'attachment'
            ? 'attachment'
            : 'inline';

        $cache = $params['cache'] ?? false;

        $bypassRange = $params['bypass_range'] ?? false;

        /** @var \Laminas\Http\PhpEnvironment\Response $response */
        $controller = $this->getController();
        $response = $controller->getResponse();

        // Write headers.
        /** @var \Laminas\Http\Headers $headers */
        $headers = $response->getHeaders()
            ->addHeaderLine(sprintf('Content-Type: %s', $contentType))
            ->addHeaderLine(sprintf('Content-Disposition: %s; filename="%s"', $dispositionMode, $filename))
            // ->addHeaderLine('Content-Description', 'File Transfer')
            ->addHeaderLine('Content-Transfer-Encoding: binary');

        if ($cache) {
            $cacheDuration = is_bool($cache) ? 30 * 24 * 60 * 60 : (int) $cache;
            $headers
                ->addHeaderLine(sprintf('Cache-Control: private, max-age=%1$d, post-check=%1$d, pre-check=%1$d', $cacheDuration))
                ->addHeaderLine(sprintf('Expires: %s', gmdate('D, d M Y H:i:s', time() + $cacheDuration) . ' GMT'));
        }

        if (!$bypassRange) {
            $headers
                ->addHeaderLine('Accept-Ranges: bytes');
        }

        // TODO Check for Apache XSendFile or Nginx: https://stackoverflow.com/questions/4022260/how-to-detect-x-accel-redirect-nginx-x-sendfile-apache-support-in-php
        // TODO Use Laminas stream response?
        // $response = new \Laminas\Http\Response\Stream();

        // Adapted from https://stackoverflow.com/questions/15797762/reading-mp4-files-with-php.
        $hasRange = !empty($_SERVER['HTTP_RANGE']);
        if ($hasRange) {
            // Start/End are pointers that are 0-based.
            $start = 0;
            $end = $filesize - 1;
            $matches = [];
            $result = preg_match('/bytes=\h*(?<start>\d+)-(?<end>\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches);
            if ($result) {
                $start = (int) $matches['start'];
                if (!empty($matches['end'])) {
                    $end = (int) $matches['end'];
                }
            }
            // Check valid range to avoid hack.
            $hasRange = ($start < $filesize && $end < $filesize && $start < $end)
                && ($start > 0 || $end < ($filesize - 1));
        }

        // $header = new Header\ContentLength();
        if ($hasRange) {
            // Set partial content.
            $response
                ->setStatusCode($response::STATUS_CODE_206);
            $headers
                ->addHeaderLine(sprintf('Content-Length: %d', $end - $start + 1))
                ->addHeaderLine(sprintf('Content-Range: bytes %1$d-%2$d/%3$d', $start, $end, $filesize));
        } else {
            $headers
                ->addHeaderLine(sprintf('Content-Length: %d', $filesize));
        }

        // Fix deprecated warning in \Laminas\Http\PhpEnvironment\Response::sendHeaders() (l. 113).
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED);

        // Send headers separately to handle large files.
        $response->sendHeaders();

        error_reporting($errorReporting);

        // Clears all active output buffers to avoid memory overflow.
        $response->setContent('');
        while (ob_get_level()) {
            ob_end_clean();
        }

        if ($hasRange) {
            $fp = @fopen($filepath, 'rb');
            $buffer = 1024 * 8;
            $pointer = $start;
            fseek($fp, $start, SEEK_SET);
            while (!feof($fp)
                && $pointer <= $end
                && connection_status() === CONNECTION_NORMAL
            ) {
                set_time_limit(0);
                echo fread($fp, min($buffer, $end - $pointer + 1));
                flush();
                $pointer += $buffer;
            }
            fclose($fp);
        } else {
            readfile($filepath);
        }

        // TODO Fix issue with session. See readme of module XmlViewer.
        ini_set('display_errors', '0');

        // Return response to avoid default view rendering and to manage events.
        return $response;
    }
}
