<?php
namespace Omeka\File;

use Omeka\File\Store\StoreInterface;
use Omeka\File\Thumbnailer\ThumbnailerInterface;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager
{
    const ORIGINAL_PREFIX = 'original';

    const THUMBNAIL_EXTENSION = 'jpg';

    /**
     * The default media type whitelist.
     */
    const MEDIA_TYPE_WHITELIST = [
        // application/*
        'application/msword',
        'application/ogg',
        'application/pdf',
        'application/rtf',
        'application/vnd.ms-access',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        'application/vnd.ms-project',
        'application/vnd.ms-write',
        'application/vnd.oasis.opendocument.chart',
        'application/vnd.oasis.opendocument.database',
        'application/vnd.oasis.opendocument.formula',
        'application/vnd.oasis.opendocument.graphics',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.text',
        'application/x-gzip',
        'application/x-ms-wmp',
        'application/x-msdownload',
        'application/x-shockwave-flash',
        'application/x-tar',
        'application/zip',
        // audio/*
        'audio/midi',
        'audio/mp4',
        'audio/mpeg',
        'audio/ogg',
        'audio/x-aac',
        'audio/x-aiff',
        'audio/x-ms-wma',
        'audio/x-ms-wax',
        'audio/x-realaudio',
        'audio/x-wav',
        // image/*
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/tiff',
        'image/x-icon',
        // text/*
        'text/css',
        'text/plain',
        'text/richtext',
        // video/*
        'video/divx',
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/quicktime',
        'video/webm',
        'video/x-ms-asf,',
        'video/x-msvideo',
        'video/x-ms-wmv',
    ];

    /**
     * Map of nonstandard-to-standard media types.
     */
    const MEDIA_TYPE_ALIASES = [
        // application/ogg
        'application/x-ogg' => 'application/ogg',
        // application/rtf
        'text/rtf' => 'application/rtf',
        // audio/midi
        'audio/mid' => 'audio/midi',
        'audio/x-midi' => 'audio/midi',
        // audio/mpeg
        'audio/mp3' => 'audio/mpeg',
        'audio/mpeg3' => 'audio/mpeg',
        'audio/x-mp3' => 'audio/mpeg',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-mpeg3' => 'audio/mpeg',
        'audio/x-mpegaudio' => 'audio/mpeg',
        'audio/x-mpg' => 'audio/mpeg',
        // audio/ogg
        'audio/x-ogg' => 'audio/ogg',
        // audio/x-aac
        'audio/aac' => 'audio/x-aac',
        // audio/x-aiff
        'audio/aiff' => 'audio/x-aiff',
        // audio/x-ms-wma
        'audio/x-wma' => 'audio/x-ms-wma',
        'audio/wma' => 'audio/x-ms-wma',
        // audio/mp4
        'audio/x-mp4' => 'audio/mp4',
        'audio/x-m4a' => 'audio/mp4',
        // audio/x-wav
        'audio/wav' => 'audio/x-wav',
        // image/bmp
        'image/x-ms-bmp' => 'image/bmp',
        // image/x-icon
        'image/icon' => 'image/x-icon',
        // video/mp4
        'video/x-m4v' => 'video/mp4',
        // video/x-ms-asf
        'video/asf' => 'video/x-ms-asf',
        // video/x-ms-wmv
        'video/wmv' => 'video/x-ms-wmv',
        // video/x-msvideo
        'video/avi' => 'video/x-msvideo',
        'video/msvideo' => 'video/x-msvideo',
    ];

    const EXTENSION_WHITELIST = [
        'aac', 'aif', 'aiff', 'asf', 'asx', 'avi', 'bmp', 'c', 'cc', 'class',
        'css', 'divx', 'doc', 'docx', 'exe', 'gif', 'gz', 'gzip', 'h', 'ico',
        'j2k', 'jp2', 'jpe', 'jpeg', 'jpg', 'm4a', 'm4v', 'mdb', 'mid', 'midi', 'mov',
        'mp2', 'mp3', 'mp4', 'mpa', 'mpe', 'mpeg', 'mpg', 'mpp', 'odb', 'odc',
        'odf', 'odg', 'odp', 'ods', 'odt', 'ogg', 'opus', 'pdf', 'png', 'pot', 'pps',
        'ppt', 'pptx', 'qt', 'ra', 'ram', 'rtf', 'rtx', 'swf', 'tar', 'tif',
        'tiff', 'txt', 'wav', 'wax', 'webm', 'wma', 'wmv', 'wmx', 'wri', 'xla', 'xls',
        'xlsx', 'xlt', 'xlw', 'zip',
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Set configuration during construction.
     *
     * @param array $config
     * @param string $tempDir
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(array $config, $tempDir, ServiceLocatorInterface $serviceLocator)
    {
        $this->config = $config;
        $this->tempDir = $tempDir;
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Factory for creating a new temporary file object.
     *
     * @return TempFile
     */
    public function createTempFile()
    {
        return new TempFile($this);
    }

    /**
     * Get the file manager configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the configured temporary directory.
     *
     * @return string
     */
    public function getTempDir()
    {
        return $this->tempDir;
    }

    /**
     * Get the media type map.
     *
     * @return array
     */
    public function getMediaTypeMap()
    {
        return $this->serviceLocator->get('Omeka\File\MediaTypeMap');
    }

    /**
     * Get the thumbnailer service.
     *
     * @return \Omeka\File\Thumbnailer\ThumbnailerInterface
     */
    public function getThumbnailer()
    {
        return $this->serviceLocator->build($this->config['thumbnailer']);
    }

    /**
     * Get the file store service.
     *
     * @return \Omeka\File\Store\StoreInterface
     */
    public function getStore()
    {
        return $this->serviceLocator->get($this->config['store']);
    }

    /**
     * Get the file downloader service.
     *
     * @return \Omeka\File\Downloader
     */
    public function getDownloader()
    {
        return $this->serviceLocator->get('Omeka\File\Downloader');
    }

    /**
     * Get the file validator service.
     *
     * @return \Omeka\File\Validator
     */
    public function getValidator()
    {
        return $this->serviceLocator->get('Omeka\File\Validator');
    }

    /**
     * Get the file uploader service.
     *
     * @return \Omeka\File\Uploader
     */
    public function getUploader()
    {
        return $this->serviceLocator->get('Omeka\File\Uploader');
    }

    /**
     * Delete original file.
     *
     * @param Media $media
     */
    public function deleteOriginal(Media $media)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        $this->getStore()->delete($storagePath);
    }

    /**
     * Get the URL to the original file.
     *
     * @param Media $media
     * @return string
     */
    public function getOriginalUrl(Media $media)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        return $this->getStore()->getUri($storagePath);
    }

    /**
     * Delete thumbnail files.
     *
     * @param Media $media
     */
    public function deleteThumbnails(Media $media)
    {
        foreach ($this->getThumbnailTypes() as $type) {
            $storagePath = $this->getStoragePath(
                $type,
                $this->getBasename($media->getFilename()),
                self::THUMBNAIL_EXTENSION
            );
            $this->getStore()->delete($storagePath);
        }
    }

    /**
     * Get the URL to the thumbnail file.
     *
     * @param string $type
     * @param Media $media
     * @return string
     */
    public function getThumbnailUrl($type, Media $media)
    {
        if (!$media->hasThumbnails() || !$this->thumbnailTypeExists($type)) {
            $fallbacks = $this->config['thumbnail_fallbacks']['fallbacks'];
            $mediaType = $media->getMediaType();
            $topLevelType = strstr($mediaType, '/', true);

            if (isset($fallbacks[$mediaType])) {
                // Prioritize a match against the full media type, e.g. "image/jpeg"
                $fallback = $fallbacks[$mediaType];
            } elseif ($topLevelType && isset($fallbacks[$topLevelType])) {
                // Then fall back on a match against the top-level type, e.g. "image"
                $fallback = $fallbacks[$topLevelType];
            } else {
                $fallback = $this->config['thumbnail_fallbacks']['default'];
            }

            $assetUrl = $this->serviceLocator->get('ViewHelperManager')->get('assetUrl');
            return $assetUrl($fallback[0], $fallback[1]);
        }

        $storagePath = $this->getStoragePath(
            $type,
            $this->getBasename($media->getFilename()),
            self::THUMBNAIL_EXTENSION
        );
        return $this->getStore()->getUri($storagePath);
    }

    /**
     * Get all thumbnail URLs, keyed by type.
     *
     * @param Media $media
     * @return array
     */
    public function getThumbnailUrls(Media $media)
    {
        $urls = [];
        foreach ($this->getThumbnailTypes() as $type) {
            $urls[$type] = $this->getThumbnailUrl($type, $media);
        }
        return $urls;
    }

    /**
     * Check whether a thumbnail type exists.
     *
     * @param string $type
     * @return bool
     */
    public function thumbnailTypeExists($type)
    {
        return array_key_exists($type, $this->config['thumbnail_types']);
    }

    /**
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getThumbnailTypes()
    {
        return array_keys($this->config['thumbnail_types']);
    }

    /**
     * Get a storage path.
     *
     * @param string $prefix The storage prefix
     * @param string $name The file name, or basename if extension is passed
     * @param null|string $extension The file extension
     * @return string
     */
    public function getStoragePath($prefix, $name, $extension = null)
    {
        return sprintf('%s/%s%s', $prefix, $name, $extension ? ".$extension" : null);
    }

    /**
     * Get the basename, given a file name.
     *
     * @param string $name
     * @return string
     */
    public function getBasename($name)
    {
        return strstr($name, '.', true) ?: $name;
    }
}
