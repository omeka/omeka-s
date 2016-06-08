<?php
namespace Omeka\File;

use Omeka\File\Store\StoreInterface;
use Omeka\File\Thumbnailer\ThumbnailerInterface;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager
{
    const ORIGINAL_PREFIX = 'original';

    const THUMBNAIL_EXTENSION = 'jpg';

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
     * Get the file store service.
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->serviceLocator->get($this->config['store']);
    }

    /**
     * Get the thumbnailer service.
     *
     * @return ThumbnailerInterface
     */
    public function getThumbnailer()
    {
        return $this->serviceLocator->get($this->config['thumbnailer']);
    }

    /**
     * Store original file.
     *
     * @param File $file
     * @return string Storage-side path for the stored file
     */
    public function storeOriginal(File $file)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $this->getStorageName($file)
        );
        $this->getStore()->put($file->getTempPath(), $storagePath);
        return $storagePath;
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
     * Create and store thumbnail derivatives.
     *
     * Gets the thumbnailer from the service manager for each call to this
     * method. This gives thumbnailers an opportunity to be non-shared services,
     * which can be useful for resolving memory allocation issues.
     *
     * @param string $source
     * @param string $storageBaseName
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails(File $file)
    {
        $thumbnailer = $this->getThumbnailer();
        $tempPaths = [];

        try {
            $thumbnailer->setSource($file->getTempPath());
            $thumbnailer->setOptions($this->config['thumbnail_options']);
            foreach ($this->config['thumbnail_types'] as $type => $config) {
                $tempPaths[$type] = $thumbnailer->create(
                    $this, $config['strategy'], $config['constraint'], $config['options']
                );
            }
        } catch (Exception\CannotCreateThumbnailException $e) {
            // Delete temporary files created before exception was thrown.
            foreach ($tempPaths as $tempPath) {
                @unlink($tempPath);
            }
            return false;
        }

        // Finally, store the thumbnails.
        foreach ($tempPaths as $type => $tempPath) {
            $storagePath = $this->getStoragePath(
                $type, $file->getStorageBaseName(), self::THUMBNAIL_EXTENSION
            );
            $this->getStore()->put($tempPath, $storagePath);
            // Delete the temporary file in case the file store hasn't already.
            @unlink($tempPath);
        }

        return true;
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

    /**
     * Get a File object for a new temporary file
     *
     * Reserves a new unique filename in the configured temp directory
     *
     * @return File
     */
    public function getTempFile()
    {
        return new File(tempnam($this->tempDir, 'omeka'));
    }

    /**
     * Get the filename extension for the original file.
     *
     * Checks the extension against a map of Internet media types. Returns a
     * "best guess" extension if the media type is known but the original
     * extension is unrecognized or nonexistent. Returns the original extension
     * if it is unrecoginized, maps to a known media type, or maps to the
     * catch-all media type, "application/octet-stream".
     *
     * @param File
     * @return string
     */
    public function getExtension(File $file)
    {
        if (!$file->getSourceName()) {
            return null;
        }

        $mediaTypeMap = $this->serviceLocator->get('Omeka\File\MediaTypeMap');
        $mediaType = $file->getMediaType();
        $extension = strtolower(substr(strrchr($file->getSourceName(), '.'), 1));

        if (isset($mediaTypeMap[$mediaType][0])
            && !in_array($mediaType, ['application/octet-stream'])
        ) {
            if ($extension) {
                if (!in_array($extension, $mediaTypeMap[$mediaType])) {
                    // Unrecognized extension.
                    $extension = $mediaTypeMap[$mediaType][0];
                }
            } else {
                // No extension.
                $extension = $mediaTypeMap[$mediaType][0];
            }
        }

        return $extension;
    }

    /**
     * Get the storage-side name for an original file
     */
    public function getStorageName(File $file)
    {
        $extension = $this->getExtension($file);
        $storageName = sprintf('%s%s', $file->getStorageBaseName(),
            $extension ? ".$extension" : null);
        return $storageName;
    }

    /**
     * Download a file.
     *
     * Pass the $errorStore object if an error should raise an API validation
     * error. Returns true on success, false on error.
     *
     * @param Zend\Uri\Http|string $uri
     * @param string $tempPath
     * @param ErrorStore|null $errorStore
     * @return bool
     */
    public function downloadFile($uri, $tempPath, ErrorStore $errorStore = null)
    {
        $client = $this->serviceLocator->get('Omeka\HttpClient');
        $client->setUri($uri)->setStream($tempPath);

        // Attempt three requests before handling an exception.
        $attempt = 0;
        while (true) {
            try {
                $response = $client->send();
                break;
            } catch (\Exception $e) {
                if (++$attempt === 3) {
                    if ($errorStore) {
                        $errorStore->addError('error', $e->getMessage());
                    }
                    $this->serviceLocator->get('Omeka\Logger')->err((string) $e);
                    return false;
                }
            }
        }

        if (!$response->isOk()) {
            $message = sprintf(
                'Error downloading "%s": %s %s',
                (string) $uri,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            if ($errorStore) {
                $errorStore->addError('error', $message);
            }
            $this->serviceLocator->get('Omeka\Logger')->err($message);
            return false;
        }

        return true;
    }
}
